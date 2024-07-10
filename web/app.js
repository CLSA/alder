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
cenozo.directive("cnImage", [
  function () {
    return {
      templateUrl: cenozoApp.getFileUrl("alder", "image.tpl.html"),
      restrict: "E",
      scope: { model: "=", },
      controller: async function ($scope) {
        $scope.directive = "cnImage";
      }
    };
  },
]);

/* ############################################################################################## */
cenozo.factory("CnImageFactory", [
  "CnSession",
  "CnHttpFactory",
  "$timeout",
  function (CnSession, CnHttpFactory, $timeout) {
    var object = function (parentModel) {
      angular.extend(this, {
        parentModel: parentModel,
        loadingImage: true,
        canvas: null,
        context: null,
        image: null,
        canvasRatio: null,
        imageRatio: null,
        imageTransform: null,
        arrowList: [],
        ellipseList: [],
        activeAnnotation: null,
        hover: { type: null, id: null },

        bounds: {
          x: { min: 0, max: 1 },
          y: { min: 0, max: 1 },
          b: { min: 0.5, max: 2.0 },
          c: { min: 1.0, max: 10.0 },
          scale: { min: 1, max: 10 },
        },

        calculateBounds: function(scale) {
          this.bounds.x.max = 0;
          this.bounds.y.max = 0;
          if (this.heightBased) {
            this.bounds.x.max = (this.view.width - this.canvas.width/scale) * this.icRatio;
            this.bounds.y.max = this.image.height - this.view.height * this.icRatio / scale;
          } else {
            this.bounds.x.max = this.image.width - this.view.width * this.icRatio / scale;
            this.bounds.y.max = (this.view.height - this.canvas.height/scale) * this.icRatio;
          }
          if (this.bounds.x.min > this.bounds.x.max) this.bounds.x.max = this.bounds.x.min;
          if (this.bounds.y.min > this.bounds.y.max) this.bounds.y.max = this.bounds.y.min;
        },

        getMousePoint: function(event) {
          if (null == this.imageTransform) return { x: null, y: null };

          const rect = this.canvas.getBoundingClientRect();
          return {
            x: (event.clientX - rect.left)/this.imageTransform.scale + this.imageTransform.ix,
            y: (event.clientY - rect.top)/this.imageTransform.scale + this.imageTransform.iy,
          };
        },

        reset: function() {
          this.imageTransform = { x: 0, y: 0, ix: 0, iy: 0, b: 1, c: 1, scale: 1 };
          this.hover = { type: null, id: null };
        },

        drawArrow: function(arrow) {
          let x0 = arrow.x0 - this.imageTransform.ix;
          let y0 = arrow.y0 - this.imageTransform.iy;
          let x1 = arrow.x1 - this.imageTransform.ix;
          let y1 = arrow.y1 - this.imageTransform.iy;
          let a = Math.atan((arrow.y1 - arrow.y0)/(arrow.x1 - arrow.x0)) + (arrow.x1 < arrow.x0 ? Math.PI : 0);

          this.context.lineWidth = 1;
          this.context.strokeStyle = "blue";
          this.context.fillStyle = "blue";

          // draw the arrow body
          this.context.beginPath();
          this.context.moveTo(x0, y0);
          this.context.lineTo(x1, y1);
          this.context.stroke();

          // draw the hover markers
          if ("arrow start" == this.hover.type && this.hover.id == arrow.id) {
            this.context.beginPath();
            this.context.arc(x0, y0, 4, 0, 2*Math.PI);
            this.context.stroke();
          }

          if ("arrow end" == this.hover.type && this.hover.id == arrow.id) {
            this.context.beginPath();
            this.context.arc(x1, y1, 4, 0, 2*Math.PI);
            this.context.stroke();
          }

          // rotate about the a head
          this.context.translate(x1, y1);
          this.context.rotate(a);
          this.context.translate(-x1, -y1);

          // draw the arrow head
          this.context.beginPath();
          this.context.moveTo(x1 + 1, y1);
          this.context.lineTo(x1 - 5, y1 - 3);
          this.context.lineTo(x1 - 5, y1 + 3);
          this.context.fill();

          // reverse the rotation
          this.context.translate(x1, y1);
          this.context.rotate(-a);
          this.context.translate(-x1, -y1);
        },

        drawEllipse: function(ellipse) {
          let x = ellipse.x - this.imageTransform.ix;
          let y = ellipse.y - this.imageTransform.iy;

          this.context.lineWidth = 1;
          this.context.strokeStyle = "blue";

          this.context.beginPath();
          this.context.ellipse(x, y, ellipse.rx, ellipse.ry, 0, 0, 2*Math.PI);
          this.context.stroke();
        },

        paint: function() {
          if (null == this.imageTransform) this.reset();

          // recalculate the canvas size based on the bounding client
          const canvasRect = this.canvas.getBoundingClientRect();
          this.canvas.width = canvasRect.width;
          this.canvas.height = canvasRect.height;
          this.canvasRatio = this.canvas.width/this.canvas.height;
          this.heightBased = this.canvasRatio > this.imageRatio;
          this.icRatio = (
            this.heightBased ?
            this.image.height/this.canvas.height :
            this.image.width/this.canvas.width
          );
          this.view = {
            width: this.heightBased ? this.canvas.height*this.imageRatio : this.canvas.width,
            height: this.heightBased ? this.canvas.height : this.canvas.width/this.imageRatio,
          };
          this.calculateBounds(this.imageTransform.scale);

          // scale the image based on the current transform
          this.context.scale(this.imageTransform.scale, this.imageTransform.scale);

          // clear background by filling with black
          this.context.rect(0, 0, this.canvas.width, this.canvas.height);
          this.context.fillStyle = "grey";
          this.context.fill();

          this.context.filter =
            "brightness(" + (100*this.imageTransform.b) + "%) " +
            "contrast(" + (100*this.imageTransform.c) + "%)";

          this.context.drawImage(
            this.image,

            // location on image to paint at the canvas origin (only affected by scalling/panning)
            this.imageTransform.x, this.imageTransform.y,

            // image's width/height (never changes)
            this.image.width, this.image.height,

            // canvas origin (never changes)
            0, 0,

            // canvas width/height (only affected by window size)
            this.view.width, this.view.height
          );

          // draw all arrows
          this.arrowList.forEach((arrow) => {
            this.drawArrow(arrow);
          });

          // draw all ellipses
          this.ellipseList.forEach((ellipse) => {
            this.drawEllipse(ellipse);
          });

          // draw the active annotation
          if (null != this.activeAnnotation) {
            if ("arrow" == this.activeAnnotation.type) {
              this.drawArrow(this.activeAnnotation);
            } else if ("ellipse" == this.activeAnnotation.type) {
              this.drawEllipse(this.activeAnnotation);
            }
          }

          // DEBUG: draw a red line around the image
          /*
          this.context.beginPath();
          this.context.lineWidth = "2";
          this.context.rect(0, 0, this.view.width, this.view.height);
          this.context.strokeStyle = "red";
          this.context.stroke();
          */
        },

        scale: function(cx, cy, inward) {
          // we either use the image height or width, depending on how it fits on the canvas
          let imageMeasure = this.heightBased ? this.image.height : this.image.width;

          // zoom in by 1/32nd of the image width
          let factor = (inward ? 1 : -1) * imageMeasure / 32;

          // determine the new scaling factor
          let newScale = imageMeasure / (imageMeasure / this.imageTransform.scale - factor);

          // restrict to scalling boundaries (1x to 10x)
          if (this.bounds.scale.min > newScale || this.bounds.scale.max < newScale) return;

          // determine the new transform coordinates
          let x = 0, y = 0;
          if (1 < newScale) {
            x = this.imageTransform.x + cx * factor / this.canvas.width * (this.heightBased?this.canvasRatio:1);
            y = this.imageTransform.y + cy * factor / this.canvas.height / (this.heightBased?1:this.canvasRatio);
          }

          // recalculate the new bounds using the new scale
          this.calculateBounds(newScale);

          // apply the new scale and redraw the image
          angular.extend(this.imageTransform, {
            x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
            y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
            scale: newScale,
          });
          angular.extend(this.imageTransform, {
            ix: this.imageTransform.x/this.icRatio,
            iy: this.imageTransform.y/this.icRatio,
          });

          this.paint();
        },

        pan: function(dx, dy) {
          let x = this.imageTransform.x + dx * this.icRatio / this.imageTransform.scale;
          let y = this.imageTransform.y + dy * this.icRatio / this.imageTransform.scale;
          angular.extend(this.imageTransform, {
            x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
            y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
          });
          angular.extend(this.imageTransform, {
            ix: this.imageTransform.x/this.icRatio,
            iy: this.imageTransform.y/this.icRatio,
          });

          this.paint();
        },

        windowLevel: function(dc, db) {
          let b = this.imageTransform.b + (this.bounds.b.max - this.bounds.b.min) * db / 600;
          let c = this.imageTransform.c + (this.bounds.c.max - this.bounds.c.min) * dc / 600;

          angular.extend(this.imageTransform, {
            b: b = b < this.bounds.b.min ? this.bounds.b.min : b > this.bounds.b.max ? this.bounds.b.max : b,
            c: c = c < this.bounds.c.min ? this.bounds.c.min : c > this.bounds.c.max ? this.bounds.c.max : c,
          });
          this.paint();
        },

        selectActiveAnnotation: function(type, id) {
          let list = this[type+"List"];
          let annotation = null;

          if (list && angular.isArray(list)) {
            // if the annotation is found then remove it from the list
            let index = list.findIndexByProperty("id", id);
            if (null != index) {
              annotation = list[index];
              list.splice(index, 1);
            }
          }

          this.activeAnnotation = annotation;
        },

        createAnnotation: function(type, point) {
          // start drawing an arrow at the given point
          this.activeAnnotation = { id: null, type: type };
          if ("arrow" == this.activeAnnotation.type) {
            angular.extend(this.activeAnnotation, { x0: point.x, y0: point.y, x1: null, y1: null, grab: "end" });
          } else if ("ellipse" == this.activeAnnotation.type) {
            angular.extend(this.activeAnnotation, { x: point.x, y: point.y, rx: null, ry: null });
          }
        },

        saveActiveAnnotation: async function() {
          if (null == this.activeAnnotation) return;

          const type = this.activeAnnotation.type;
          let annotation = this.activeAnnotation;
          let data = null;
          let valid = false;

          if ("arrow" == type) {
            // prepare the annotation data
            data = { x0: annotation.x0, y0: annotation.y0, x1: annotation.x1, y1: annotation.y1 };

            // check if the arrow is valid
            valid = (
              null != this.activeAnnotation.x0 && null != this.activeAnnotation.x1 &&
              null != this.activeAnnotation.y0 && null != this.activeAnnotation.y1 && (
                this.activeAnnotation.x0 != this.activeAnnotation.x1 ||
                this.activeAnnotation.y0 != this.activeAnnotation.y1
              )
            );
          } else if ("ellipse" == type) {
            // prepare the annotation data
            data = { x: annotation.x, y: annotation.y, rx: annotation.rx, ry: annotation.ry };

            // check if the ellipse is valid
            valid = (
              null != this.activeAnnotation.rx && 4 <= this.activeAnnotation.rx &&
              null != this.activeAnnotation.ry && 4 <= this.activeAnnotation.ry
            );
          }

          if (valid && null != annotation) {
            if ("arrow" == type) {
              // add the new arrow
              this.arrowList.push(annotation);
            } else if ("ellipse" == type) {
              // add the new ellipse
              this.ellipseList.push(annotation);
            }
          }

          this.activeAnnotation = null;
          this.paint();

          if (valid && null != annotation && null != data) {
            // TODO: handle server errors
            if (null == annotation.id) {
              // create a new annotation
              const response = await CnHttpFactory.instance({
                path: [this.parentModel.getServiceResourcePath(), type].join("/"),
                data: data,
              }).post();
              annotation.id = response.data;
            } else {
              // update an existing annotation
              await CnHttpFactory.instance({
                path: [this.parentModel.getServiceResourcePath(), type, annotation.id].join("/"),
                data: data,
              }).patch();
            }
          }
        },

        updateHover: function(point) {
          // check if we're hovering over an arrow
          let hover = this.arrowList.some((arrow) => {
            if (4 > Math.abs(point.x - arrow.x0) && 4 > Math.abs(point.y - arrow.y0)) {
              angular.extend(this.hover, { type: "arrow start", id: arrow.id });
              return true;
            }

            if (4 > Math.abs(point.x - arrow.x1) && 4 > Math.abs(point.y - arrow.y1)) {
              angular.extend(this.hover, { type: "arrow end", id: arrow.id });
              return true;
            }
          });

          if (!hover) {
            // check if we're hovering over an ellipse
            hover = this.ellipseList.some((ellipse) => {
              if (4 > Math.abs(point.x - ellipse.x) && 4 > Math.abs(point.y - ellipse.y)) {
                angular.extend(this.hover, { type: "ellipse", id: ellipse.id });
                return true;
              }
            });

            // if nothing is under the mouse then reset the hover property
            if (!hover) this.hover = { type: null, id: null };
          }

          this.paint();
        },

        translateActiveAnnotation: async function(point) {
          if ("arrow" == this.activeAnnotation.type) {
            let dx = 0, dy = 0;
            if ("start" == this.activeAnnotation.grab) {
              dx = point.x - this.activeAnnotation.x0;
              dy = point.y - this.activeAnnotation.y0;
            } else if ("end" == this.activeAnnotation.grab) {
              dx = point.x - this.activeAnnotation.x1;
              dy = point.y - this.activeAnnotation.y1;
            }
            this.activeAnnotation.x0 += dx;
            this.activeAnnotation.y0 += dy;
            this.activeAnnotation.x1 += dx;
            this.activeAnnotation.y1 += dy;
          } else if ("annotation" == this.activeAnnotation.type) {
            this.activeAnnotation.x = point.x;
            this.activeAnnotation.y = point.y;
          }

          this.paint();
        },

        transformActiveAnnotation: function(point) {
          if (null != this.activeAnnotation) {
            if ("arrow" == this.activeAnnotation.type) {
              // move the grabbed end of the arrow
              if ("start" == this.activeAnnotation.grab) {
                angular.extend(this.activeAnnotation, { x0: point.x, y0: point.y });
              } else if ("end" == this.activeAnnotation.grab) {
                angular.extend(this.activeAnnotation, { x1: point.x, y1: point.y });
              }
            } else if ("ellipse" == this.activeAnnotation.type) {
              // resize the ellipse
              angular.extend(this.activeAnnotation, {
                rx: Math.abs(point.x - this.activeAnnotation.x),
                ry: Math.abs(point.y - this.activeAnnotation.y),
              });
            }
            this.paint();
          }
        },

        createEventListeners: function() {
          // redraw the image when the window is resized
          var resizeWait = false;
          window.addEventListener("resize", () => {
            if (!resizeWait) {
              this.paint();
              resizeWait = true;
              $timeout(() => { this.paint(); resizeWait = false; }, 50);
            }
          });

          // capture all key inputs when the mouse is on the canvas
          window.addEventListener("keydown", async (event) => {
            if (this.canvas.parentNode.matches(":hover")) {
              event.preventDefault();
              event.stopPropagation();

              if ("Delete" == event.key) {
                // delete the hovered object
                if ("start arrow" == this.hover.type || "end arrow" == this.hover.type) {
                  let index = this.arrowList.findIndexByProperty("id", this.hover.id);
                  if (null != index) {
                    let id = this.hover.id;
                    this.arrowList.splice(index, 1);
                    this.hover = { type: null, id: null };
                    this.paint();

                    await CnHttpFactory.instance({
                      path: this.parentModel.getServiceResourcePath() + "/arrow/" + id
                    }).delete();
                  }
                } else if ("ellipse" == this.hover.type) {
                  let index = this.ellipseList.findIndexByProperty("id", this.hover.id);
                  if (null != index) {
                    let id = this.hover.id;
                    this.ellipseList.splice(index, 1);
                    this.hover = { type: null, id: null };
                    this.paint();

                    await CnHttpFactory.instance({
                      path: this.parentModel.getServiceResourcePath() + "/ellipse/" + id
                    }).delete();
                  }
                }
              } else if ("Escape" == event.key) {
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

            ondblclick: (event) => {
            },

            onmousedown: (event) => {
              let button = null;
              if (0 == event.button) button = "left";
              else if (1 == event.button) button = "middle";
              else if (2 == event.button) button = "right";

              const point = this.getMousePoint(event);
              if (event.altKey) {
                // do nothing
              } else if (event.ctrlKey) {
                // left click creates an arrow
                if ("left" == button) this.createAnnotation("arrow", point);
              } else if(event.shiftKey) {
                // left click creates an ellipse
                if ("left" == button) this.createAnnotation("ellipse", point);
              } else {
                // left click or middle click grabs hovered annotations
                if (["left", "middle"].includes(button) && null != this.hover.id) {
                  // but only if there isn't already an active annotation 
                  if (null == this.activeAnnotation) {
                    this.selectActiveAnnotation(this.hover.type.replace(/ (start|end)/, ""), this.hover.id);

                    // if this is an arrow then set which end we've grabbed it by
                    if (null != this.activeAnnotation) {
                      if ("arrow start" == this.hover.type) {
                        this.activeAnnotation.grab = "start";
                      } else if ("arrow end" == this.hover.type) {
                        this.activeAnnotation.grab = "end";
                      }
                    }
                  }
                }
              }
            },

            onmouseup: (event) => {
              let button = null;
              if (0 == event.button) button = "left";
              else if (1 == event.button) button = "middle";
              else if (2 == event.button) button = "right";

              // save the annotation if we're letting go of an active annotation (left or middle mouse button only)
              if (["left", "middle"].includes(button) && null != this.activeAnnotation) {
                this.saveActiveAnnotation();
              }
            },

            onwheel: (event) => {
              event.preventDefault();
              event.stopPropagation();

              // use absolute coordinates
              const rect = this.canvas.getBoundingClientRect();
              this.scale( event.clientX - rect.left, event.clientY - rect.top, 0 < event.wheelDelta);
            },

            onmousemove: (event) => {
              let button = null;
              if (1 == event.buttons) button = "left";
              else if (4 == event.buttons) button = "middle";
              else if (2 == event.buttons) button = "right";

              const point = this.getMousePoint(event);
              if (null == button) {
                // update which annotation is behing hovered over
                if (!event.altKey && !event.ctrlKey && !event.shiftKey) {
                  this.updateHover(point);
                }
              } else if ("left" == button) {
                // move the active annotation
                this.transformActiveAnnotation(point);
              } else if ("middle" == button) {
                // translate the active annotation
                if (null != this.activeAnnotation) {
                  this.translateActiveAnnotation(point);
                } else {
                  // if there is no annotation to translate then pan the image
                  this.pan(-event.movementX, -event.movementY);
                }
              } else if ("right" == button) {
                this.windowLevel(event.movementX, -event.movementY);
              }
            },
          });
        },

        onView: async function() {
          this.loadingImage = true;
          try {
            this.canvas = document.getElementById("canvas");
            const dpRatio = window.devicePixelRatio;
            const canvasRect = this.canvas.getBoundingClientRect();
            this.canvas.width = canvasRect.width * dpRatio;
            this.canvas.height = canvasRect.height * dpRatio;

            this.context = this.canvas.getContext("2d")
            this.context.scale(dpRatio, dpRatio);
            this.context.font = "24px Arial";
            this.context.fillText("Loading image...", 20, 40);

            this.image = new Image();
            this.image.onload = () => {
              this.imageRatio = this.image.width / this.image.height;
              this.reset();
              this.paint();
            }

            // load the image and all annotation data
            const [imageResponse, arrowResponse, ellipseResponse] = await Promise.all([
              CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath(),
                data: { select: { column: "image" } },
              }).get(),

              CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath() + "/arrow",
                data: { modifier: { where: { column: "user_id", operator: "=", value: CnSession.user.id } } },
              }).get(),

              CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath() + "/ellipse",
                data: { modifier: { where: { column: "user_id", operator: "=", value: CnSession.user.id } } },
              }).get(),
            ]);

            this.image.src = imageResponse.data.image.data;

            this.arrowList = arrowResponse.data.reduce((list, arrow) => {
              list.push({
                id: arrow.id,
                type: "arrow",
                x0: arrow.x0,
                y0: arrow.y0,
                x1: arrow.x1,
                y1: arrow.y1
              });
              return list;
            }, []);

            this.ellipseList = ellipseResponse.data.reduce((list, ellipse) => {
              list.push({
                id: ellipse.id,
                type: "ellipse",
                x: ellipse.x,
                y: ellipse.y,
                rx: ellipse.rx,
                ry: ellipse.ry
              });
              return list;
            }, []);
            console.log(this.ellipseList);

            this.loadingImage = false;

            this.createEventListeners();
          } finally {
            this.loadingImage = false;
          }
        },
      });
    };

    return {
      instance: function (parentModel) {
        return new object(parentModel);
      },
    };
  },
]);
