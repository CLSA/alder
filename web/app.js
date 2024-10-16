"use strict";

var cenozo = angular.module("cenozo");

cenozo.controller("HeaderCtrl", [
  "$scope",
  "CnBaseHeader",
  function ($scope, CnBaseHeader) {
    // copy all properties from the base header
    CnBaseHeader.construct($scope);
  },
]);

/* ############################################################################################## */
cenozo.directive("cnImageDisplay", [
  "CnImageDisplayFactory",
  function (CnImageDisplayFactory) {
    return {
      templateUrl: cenozoApp.getFileUrl("alder", "image_display.tpl.html"),
      restrict: "E",
      scope: { model: '=', },
    };
  },  
]); 


/* ############################################################################################## */
cenozo.factory("CnImageDisplayFactory", [
  "CnSession",
  "CnHttpFactory",
  function (CnSession, CnHttpFactory) {
    var object = function () {
      angular.extend(this, {
        isTypist: false,
        reviewUserId: null,
        imageList: [],
        currentImage: null,

        canvas: null,
        context: null,
        image: null,
        canvasRatio: null,
        imageRatio: null,
        transform: { x: 0, y: 0, b: 1, c: 1, scale: 1 },
        activeAnnotation: null,
        hover: { type: null, handle: null, id: null },

        bounds: {
          x: { min: 0, max: 1 },
          y: { min: 0, max: 1 },
          b: { min: 0.5, max: 2.0 },
          c: { min: 1.0, max: 10.0 },
          scale: { min: 1, max: 16 },
        },

        selectImage: function(index) {
          const image = this.imageList.findByProperty("index", index);
          if (null != image) {
            this.currentImage = image;
            this.loadImage();
          }
        },

        nextImage: function () {
          if (null != this.currentImage) this.selectImage(this.currentImage.index+1);
        },

        prevImage: function () {
          if (null != this.currentImage) this.selectImage(this.currentImage.index-1);
        },

        onView: async function () {
          this.isLoading = true;

          try {
            await Promise.all(
              this.imageList.map(async (image) => {
                let promiseList = [
                  CnHttpFactory.instance({
                    path: ["image", image.imageId].join("/"),
                    data: { select: { column: ["filename", "image"] } },
                  }).get()
                ];

                // load all annotations if an analysisId was provided
                if (image.analysisId) {
                  promiseList.push(
                    CnHttpFactory.instance({
                      path: ["analysis", image.analysisId, "annotation"].join("/"),
                    }).query()
                  );
                }

                let [imageResponse, annotationResponse] = await Promise.all(promiseList);

                angular.extend(image, {
                  filename: imageResponse.data.filename,
                  imageSrc: 0 == imageResponse.data.image.size ? null : imageResponse.data.image.data,
                });

                if (image.analysisId) {
                  image.annotationList = annotationResponse.data.reduce((list, a) => {
                    list.push({ id: a.id, type: a.type, x0: a.x0, y0: a.y0, x1: a.x1, y1: a.y1 }); 
                    return list;
                  }, []);
                }
              })
            );
          } finally {
            this.isLoading = false;
          }

          // prepare the canvas
          this.canvas = document.getElementById("canvas");
          const rect = this.canvas.getBoundingClientRect();
          this.canvas.width = rect.width*window.devicePixelRatio;
          this.canvas.height = rect.height*window.devicePixelRatio;

          this.context = this.canvas.getContext("2d")
          this.context.scale(window.devicePixelRatio, window.devicePixelRatio);
          this.drawNotice("loading image(s)...");

          this.image = new Image();
          this.image.onload = () => {
            this.imageRatio = this.image.width/this.image.height;
            this.reset();
            this.paint();
          }

          if (0 < this.imageList.length) {
            this.selectImage(0);
          } else {
            this.currentImage = null;
          }
        },

        loadImage: function () {
          if (null == this.currentImage || null == this.currentImage.imageSrc) {
            this.drawNotice("No image found");
          } else {
            this.image.src = this.currentImage.imageSrc; 
            this.createEventListeners();
          }
        },

        updateView: function(scale) {
          const rect = this.canvas.getBoundingClientRect();
          this.canvas.width = rect.width*window.devicePixelRatio;
          this.canvas.height = rect.height*window.devicePixelRatio;
          this.canvasRatio = this.canvas.width/this.canvas.height;
          this.heightBased = this.canvasRatio > this.imageRatio;
          this.irRatio = this.heightBased ? this.image.height/rect.height : this.image.width/rect.width;
          this.icRatio = (
            this.heightBased ? this.image.height/this.canvas.height : this.image.width/this.canvas.width
          );
          this.canvasView = {
            x: 0,
            y: 0,
            width: this.heightBased ? this.canvas.height*this.imageRatio : this.canvas.width,
            height: this.heightBased ? this.canvas.height : this.canvas.width/this.imageRatio,
          };

          this.bounds.x.max = 0;
          this.bounds.y.max = 0;
          if (this.heightBased) {
            this.bounds.x.max = this.image.width - this.image.height/scale*this.canvasRatio;
            this.bounds.y.max = this.image.height - this.image.height/scale;
          } else {
            this.bounds.x.max = this.image.width - this.image.width/scale;
            this.bounds.y.max = this.image.height - this.image.width/scale/this.canvasRatio;
          }
          if (this.bounds.x.min > this.bounds.x.max) this.bounds.x.max = this.bounds.x.min;
          if (this.bounds.y.min > this.bounds.y.max) this.bounds.y.max = this.bounds.y.min;
        },

        canvasToImage: function(x, y) {
          const rect = this.canvas.getBoundingClientRect();
          return {
            x: (x - rect.x)*this.irRatio/this.transform.scale + this.transform.x,
            y: (y - rect.y)*this.irRatio/this.transform.scale + this.transform.y,
          };
        },

        imageToCanvas: function(x, y) {
          const rect = this.canvas.getBoundingClientRect();
          return {
            x: (x - this.transform.x)*this.transform.scale/this.icRatio,
            y: (y - this.transform.y)*this.transform.scale/this.icRatio,
          };
        },

        reset: function() {
          this.transform = { x: 0, y: 0, b: 1, c: 1, scale: 1 };
          this.hover = { type: null, handle: null, id: null };
        },

        drawNotice: function(notice) {
          this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
          this.context.font = "18px Arial";
          this.context.fillText(notice, 20, 40);
        },

        drawAnnotation: function(annotation) {
          let p0 = this.imageToCanvas(annotation.x0, annotation.y0);
          let p1 = this.imageToCanvas(annotation.x1, annotation.y1);

          this.context.lineWidth = 2;
          this.context.strokeStyle = "blue";
          this.context.fillStyle = "blue";

          if ("arrow" == annotation.type) {
            let a = Math.atan((p1.y - p0.y)/(p1.x - p0.x)) + (p1.x < p0.x ? Math.PI : 0);

            // draw the arrow body
            this.context.beginPath();
            this.context.moveTo(p0.x, p0.y);
            this.context.lineTo(p1.x, p1.y);
            this.context.stroke();

            // draw the hover markers
            if (this.hover.id == annotation.id) {
              if (["all", "start"].includes(this.hover.handle)) {
                this.context.beginPath();
                this.context.arc(p0.x, p0.y, 6, 0, 2*Math.PI);
                this.context.stroke();
              }

              if (["all", "end"].includes(this.hover.handle)) {
                this.context.beginPath();
                this.context.arc(p1.x, p1.y, 6, 0, 2*Math.PI);
                this.context.stroke();
              }
            }

            // rotate about the a head
            this.context.translate(p1.x, p1.y);
            this.context.rotate(a);
            this.context.translate(-p1.x, -p1.y);

            // draw the arrow head
            this.context.beginPath();
            this.context.moveTo(p1.x + 1, p1.y);
            this.context.lineTo(p1.x - 10, p1.y - 6);
            this.context.lineTo(p1.x - 10, p1.y + 6);
            this.context.fill();

            // reverse the rotation
            this.context.translate(p1.x, p1.y);
            this.context.rotate(-a);
            this.context.translate(-p1.x, -p1.y);
          } else if ("ellipse" == annotation.type) {
            let cx = (p0.x + p1.x)/2;
            let cy = (p0.y + p1.y)/2;
            let rx = Math.abs(p1.x - p0.x)/2;
            let ry = Math.abs(p1.y - p0.y)/2;

            this.context.beginPath();
            this.context.ellipse(cx, cy, rx, ry, 0, 0, 2*Math.PI);
            this.context.stroke();

            // draw the hover markers
            if (this.hover.id == annotation.id) {
              if (["all", "nw"].includes(this.hover.handle)) {
                this.context.beginPath();
                this.context.arc(cx - rx, cy - ry, 6, 0, 2*Math.PI);
                this.context.stroke();
              }

              if (["all", "sw"].includes(this.hover.handle)) {
                this.context.beginPath();
                this.context.arc(cx - rx, cy + ry, 6, 0, 2*Math.PI);
                this.context.stroke();
              }

              if (["all", "se"].includes(this.hover.handle)) {
                this.context.beginPath();
                this.context.arc(cx + rx, cy + ry, 6, 0, 2*Math.PI);
                this.context.stroke();
              }

              if (["all", "ne"].includes(this.hover.handle)) {
                this.context.beginPath();
                this.context.arc(cx + rx, cy - ry, 6, 0, 2*Math.PI);
                this.context.stroke();
              }
            }
          }
        },

        paint: function() {
          if (null == this.transform) this.reset();

          // recalculate the view based on the current scale
          this.updateView(this.transform.scale);

          // scale the image based on the current transform
          this.context.scale(this.transform.scale, this.transform.scale);

          // clear background by filling with black
          this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);

          this.context.filter =
            "brightness(" + (100*this.transform.b) + "%) " +
            "contrast(" + (100*this.transform.c) + "%)";

          this.context.drawImage(
            this.image,

            // location on image to paint at the canvas origin (only affected by scalling/panning)
            this.transform.x, this.transform.y,

            // image's width/height (never changes)
            this.image.width, this.image.height,

            // canvas view origin (never changes)
            this.canvasView.x, this.canvasView.y,

            // canvas view width/height (only affected by window size)
            this.canvasView.width, this.canvasView.height
          );

          this.context.scale(1/this.transform.scale, 1/this.transform.scale);

          // draw all annotations
          if (this.currentImage.analysisId) {
            this.currentImage.annotationList.forEach(annotation => this.drawAnnotation(annotation));
          }

          // draw the active annotation
          if (null != this.activeAnnotation) {
            this.drawAnnotation(this.activeAnnotation);
          }
        },

        // cx and cy must be between 0 and 1 based on the center of scaling
        // inward should be true to zoom in, false to zoom out
        scale: function(cx, cy, inward) {
          // we either use the image height or width, depending on how it fits on the canvas
          let imageMeasure = this.heightBased ? this.image.height : this.image.width;

          // zoom in by 1/32nd of the image width
          let scaleFactor = (inward ? 1 : -1)*imageMeasure/32;

          // determine the new scale value
          let newScale = imageMeasure/(imageMeasure/this.transform.scale - scaleFactor);

          // restrict to scalling boundaries (1x to 10x)
          if (this.bounds.scale.min > newScale || this.bounds.scale.max < newScale) return;

          // determine the new transform coordinates
          let x = 0, y = 0;
          if (1 < newScale) {
            x = this.transform.x + scaleFactor*cx*(this.heightBased ? this.canvasRatio : 1);
            y = this.transform.y + scaleFactor*cy*(this.heightBased ? 1 : 1/this.canvasRatio);
          }

          // recalculate the view based on the new scale
          this.updateView(newScale);

          // apply the new scale and redraw the image
          angular.extend(this.transform, {
            x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
            y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
            scale: newScale,
          });

          this.paint();
        },

        pan: function(dx, dy) {
          let x = this.transform.x + dx*this.irRatio/this.transform.scale;
          let y = this.transform.y + dy*this.irRatio/this.transform.scale;
          angular.extend(this.transform, {
            x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
            y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
          });

          this.paint();
        },

        windowLevel: function(dc, db) {
          let b = this.transform.b + (this.bounds.b.max - this.bounds.b.min)*db/600;
          let c = this.transform.c + (this.bounds.c.max - this.bounds.c.min)*dc/600;

          angular.extend(this.transform, {
            b: b = b < this.bounds.b.min ? this.bounds.b.min : b > this.bounds.b.max ? this.bounds.b.max : b,
            c: c = c < this.bounds.c.min ? this.bounds.c.min : c > this.bounds.c.max ? this.bounds.c.max : c,
          });
          this.paint();
        },

        createAnnotation: function(type, point) {
          if (!this.currentImage.analysisId || !this.isTypist) return;

          this.activeAnnotation = { id: null, type: type };
          angular.extend(this.activeAnnotation, {
            x0: point.x,
            y0: point.y,
            x1: null,
            y1: null,
            grab: "arrow" == this.activeAnnotation.type ? "end" : "se"
          });
        },

        updateActiveAnnotation: function(point) {
          if (!this.currentImage.analysisId || !this.isTypist) return;

          let annotation = null;
          if (null != this.hover.id) {

            // if the annotation is found then remove it from the list
            const annotationList = this.currentImage.annotationList;
            let index = annotationList.findIndexByProperty("id", this.hover.id);
            if (null != index) {
              annotation = annotationList[index];
              annotationList.splice(index, 1);

              // set where we've grabbed the annotation
              annotation.grab = "all" == this.hover.handle ? point : this.hover.handle;
            }
          }

          this.activeAnnotation = annotation;
        },

        deleteActiveAnnotation: async function() {
          if (null == this.hover.id || !this.currentImage.analysisId || !this.isTypist) return;

          const annotationList = this.currentImage.annotationList;
          let index = annotationList.findIndexByProperty("id", this.hover.id);
          if (null != index) {
            let id = this.hover.id;
            annotationList.splice(index, 1);
            this.hover = { handle: null, id: null };
            this.paint();

            await CnHttpFactory.instance({
              path: ["analysis", this.currentImage.analysisId, "annotation", id].join("/"),
            }).delete();
          }
        },

        saveActiveAnnotation: async function() {
          if (null == this.activeAnnotation || !this.currentImage.analysisId || !this.isTypist) return;

          let annotation = this.activeAnnotation;
          let data = {
            type: annotation.type,
            x0: annotation.x0,
            y0: annotation.y0,
            x1: annotation.x1,
            y1: annotation.y1
          };

          // check if the annotation is valid
          let valid = (
            null != this.activeAnnotation.x0 && null != this.activeAnnotation.x1 &&
            null != this.activeAnnotation.y0 && null != this.activeAnnotation.y1 && (
              this.activeAnnotation.x0 != this.activeAnnotation.x1 ||
              this.activeAnnotation.y0 != this.activeAnnotation.y1
            )
          );

          if (valid && null != annotation) {
            try {
              let httpObj = {
                path: ["analysis", this.currentImage.analysisId, "annotation"].join("/"),
                data: data,
                onError: function (error) {}, // do nothing
              };

              if (null == annotation.id) {
                // create a new annotation
                const response = await CnHttpFactory.instance(httpObj).post();
                annotation.id = response.data;
              } else {
                // update an existing annotation
                httpObj.path += "/" + annotation.id;
                await CnHttpFactory.instance(httpObj).patch();
              }

              // only add the annotation to the list if it was successfully created
              this.currentImage.annotationList.push(annotation);
            } catch (error) {
              // errors are handled above in the onError functions
            }
          }

          this.activeAnnotation = null;
          this.paint();
        },

        updateHover: function(point) {
          if (!this.currentImage.analysisId || !this.isTypist) return;

          // distance to handle varies with scaling
          const dh = 16/this.transform.scale;

          // 1. check if we're hovering over an annotation handle
          const annotationList = this.currentImage.annotationList;
          let hover = annotationList.some((annotation) => {
            let handle = null;
            if (dh > Math.abs(point.x - annotation.x0) && dh > Math.abs(point.y - annotation.y0)) {
              handle = "arrow" == annotation.type ? "start" : "nw";
            } else if (dh > Math.abs(point.x - annotation.x1) && dh > Math.abs(point.y - annotation.y0)) {
              handle = "ne";
            } else if (dh > Math.abs(point.x - annotation.x1) && dh > Math.abs(point.y - annotation.y1)) {
              handle = "arrow" == annotation.type ? "end" : "se";
            } else if (dh > Math.abs(point.x - annotation.x0) && dh > Math.abs(point.y - annotation.y1)) {
              handle = "sw";
            }

            if (null != handle) {
              angular.extend(this.hover, { handle: handle, id: annotation.id });
              return true;
            }
          });

          // 2. check if we're hovering over an annotation body
          if (!hover) {
            hover = annotationList.some((annotation) => {
              let b = {
                x0: annotation.x0 < annotation.x1 ? annotation.x0 : annotation.x1,
                x1: annotation.x0 < annotation.x1 ? annotation.x1 : annotation.x0,
                y0: annotation.y0 < annotation.y1 ? annotation.y0 : annotation.y1,
                y1: annotation.y0 < annotation.y1 ? annotation.y1 : annotation.y0,
              };

              if (b.x0 <= point.x && point.x <= b.x1 && b.y0 <= point.y && point.y <= b.y1) {
                angular.extend(this.hover, { handle: "all", id: annotation.id });
                return true;
              }
            });
          }

          // 3. we aren't hovering over any annotation
          if (!hover) this.hover = { handle: null, id: null };

          this.paint();
        },

        transformActiveAnnotation: function(point) {
          if (!this.currentImage.analysisId || !this.isTypist) return;

          let aa = this.activeAnnotation;
          if (null != aa) {
            // change which ellipse handle is being grabbed, if necessary
            if ("nw" == aa.grab) {
              aa.grab = point.x > aa.x1 ? "ne" : point.y > aa.y1 ? "sw" : "nw";
            } else if ("ne" == aa.grab) {
              aa.grab = point.x < aa.x0 ? "nw" : point.y > aa.y1 ? "se" : "ne";
            } else if ("se" == aa.grab) {
              aa.grab = point.x < aa.x0 ? "sw" : point.y < aa.y0 ? "ne" : "se";
            } else if ("sw" == aa.grab) {
              aa.grab = point.x > aa.x1 ? "se" : point.y < aa.y0 ? "nw" : "sw";
            }
            this.hover.handle = aa.grab;

            if (["nw", "start"].includes(aa.grab)) {
              // move the x0,y0 point
              angular.extend(aa, { x0: point.x, y0: point.y });
            } else if ("ne" == aa.grab) {
              // move the x1,y0 point
              angular.extend(aa, { x1: point.x, y0: point.y });
            } else if (["se", "end"].includes(aa.grab)) {
              // move the x1,y1 point
              angular.extend(aa, { x1: point.x, y1: point.y });
            } else if ("sw" == aa.grab) {
              // move the x0,y1 point
              angular.extend(aa, { x0: point.x, y1: point.y });
            } else if (angular.isObject(aa.grab)) {
              // move all points
              aa.x0 += point.x - aa.grab.x;
              aa.y0 += point.y - aa.grab.y;
              aa.x1 += point.x - aa.grab.x;
              aa.y1 += point.y - aa.grab.y;

              // update the grab point
              aa.grab.x = point.x;
              aa.grab.y = point.y;
            }

            this.paint();
          }
        },

        createEventListeners: function() {
          // redraw the image when the window is resized
          window.addEventListener("resize", () => {
            this.paint();
          });

          // capture all key inputs when the mouse is on the canvas
          window.addEventListener("keydown", async (event) => {
            if (this.canvas.parentNode.matches(":hover")) {
              event.preventDefault();
              event.stopPropagation();

              if ("Delete" == event.key) {
                // delete the hovered annotation
                await this.deleteActiveAnnotation();
              } else if ("Escape" == event.key) {
                // reset the image
                this.reset();
                this.paint();
              }
            }
          });

          window.addEventListener("mouseout", (event) => {
            if (this.activeAnnotation) {
              this.activeAnnotation = null;
              this.paint();
            }
          });

          // track mouse operations
          angular.extend(this.canvas, {
            oncontextmenu: (event) => {
              event.preventDefault();
              event.stopPropagation();
            },

            onmousedown: (event) => {
              let button = null;
              if (0 == event.button) button = "left";
              else if (1 == event.button) button = "middle";
              else if (2 == event.button) button = "right";

              const point = this.canvasToImage(event.clientX, event.clientY);
              if (event.altKey) {
                // do nothing
              } else if (event.ctrlKey) {
                // left click creates an ellipse
                if ("left" == button) this.createAnnotation("ellipse", point);
              } else if(event.shiftKey) {
                // left click creates an arrow
                if ("left" == button) this.createAnnotation("arrow", point);
              } else {
                // left click grabs hovered annotations (if none are already active)
                if ("left" == button && null == this.activeAnnotation) {
                  this.updateActiveAnnotation(point);
                }
              }
            },

            onmouseup: async (event) => {
              let button = null;
              if (0 == event.button) button = "left";
              else if (1 == event.button) button = "middle";
              else if (2 == event.button) button = "right";

              // save the annotation if we're letting go of an active annotation
              if ("left" == button && null != this.activeAnnotation) {
                await this.saveActiveAnnotation();
              }
            },

            onwheel: (event) => {
              event.preventDefault();
              event.stopPropagation();

              // send the center point that we are scaling in on in percent [0,1]
              const rect = this.canvas.getBoundingClientRect();
              this.scale(event.layerX/rect.width, event.layerY/rect.height, 0 < event.wheelDelta);
            },

            onmousemove: (event) => {
              let button = null;
              if (1 == event.buttons) button = "left";
              else if (4 == event.buttons) button = "middle";
              else if (2 == event.buttons) button = "right";

              const point = this.canvasToImage(event.clientX, event.clientY);
              if (null == button) {
                // update which annotation is behing hovered over
                if (!event.altKey && !event.ctrlKey && !event.shiftKey) {
                  this.updateHover(point);
                }
              } else if ("left" == button) {
                // move the active annotation
                this.transformActiveAnnotation(point);
              } else if ("middle" == button) {
                this.pan(-event.movementX, -event.movementY);
              } else if ("right" == button) {
                this.windowLevel(event.movementX, -event.movementY);
              }
            },
          });
        },
      });
    };
    return {
      instance: function () {
        return new object();
      },
    };
  },
]);
