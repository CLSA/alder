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
        activeArrow: null,

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
          if (arrow.hover0) {
            this.context.beginPath();
            this.context.arc(x0, y0, 4, 0, 2*Math.PI);
            this.context.stroke();
          }

          if (arrow.hover1) {
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

          // draw the active arrow
          if (null != this.activeArrow) {
            this.drawArrow(this.activeArrow);
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

        createActiveArrow: function(point) {
          // start drawing an arrow a x, y with the end of the arrow grabbed
          this.activeArrow = {
            id: null,
            x0: point.x,
            y0: point.y,
            x1: null,
            y1: null,
            grab0: false,
            grab1: true
          };
        },

        saveActiveArrow: async function() {
          // add the arrow to the list
          if (null != this.activeArrow) {
            let arrow = null;

            // check if the arrow is valid
            let valid = (
              null != this.activeArrow.x0 && null != this.activeArrow.x1 &&
              null != this.activeArrow.y0 && null != this.activeArrow.y1 && (
                this.activeArrow.x0 != this.activeArrow.x1 || this.activeArrow.y0 != this.activeArrow.y1
              )
            );

            if (valid) {
              // add the new arrow
              arrow = {
                id: this.activeArrow.id,
                x0: this.activeArrow.x0,
                y0: this.activeArrow.y0,
                x1: this.activeArrow.x1,
                y1: this.activeArrow.y1,
                hover0: false,
                hover1: false
              };
              this.arrowList.push(arrow);
            }

            this.activeArrow = null;
            this.paint();

            if (valid) {
              // TODO: handle server errors
              if (null == arrow.id) {
                const response = await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath() + "/arrow",
                  data: {
                    x0: arrow.x0,
                    y0: arrow.y0,
                    x1: arrow.x1,
                    y1: arrow.y1,
                  }
                }).post();
                arrow.id = response.data;
              } else {
                await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath() + "/arrow/" + arrow.id,
                  data: {
                    x0: arrow.x0,
                    y0: arrow.y0,
                    x1: arrow.x1,
                    y1: arrow.y1,
                  }
                }).patch();
              }
            }
          }
        },

        translateActiveArrow: async function(dx, dy) {
          this.activeArrow.x0 += dx;
          this.activeArrow.y0 += dy;
          this.activeArrow.x1 += dx;
          this.activeArrow.y1 += dy;
          this.paint();
        },

        moveActiveArrowStartPoint: function(point) {
          angular.extend(this.activeArrow, { x0: point.x, y0: point.y });
          this.paint();
        },

        moveActiveArrowEndPoint: function(point) {
          angular.extend(this.activeArrow, { x1: point.x, y1: point.y });
          this.paint();
        },

        highlightArrows: function(point) {
          // only ever hover over a single arrow
          let hover = false;
          this.arrowList.forEach((arrow) => {
            if (hover) {
              angular.extend(arrow, {hover0: false, hover1: false});
            } else if (4 > Math.abs(point.x - arrow.x0) && 4 > Math.abs(point.y - arrow.y0)) {
              angular.extend(arrow, {hover0: true, hover1: false});
              hover = true;
            } else if (4 > Math.abs(point.x - arrow.x1) && 4 > Math.abs(point.y - arrow.y1)) {
              angular.extend(arrow, {hover0: false, hover1: true});
              hover = true;
            } else {
              angular.extend(arrow, {hover0: false, hover1: false});
            }
          });
          this.paint();
        },

        drawElipse: function() {
        },

        drawSquare: function() {
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
                let index = this.arrowList.findIndexByProperty("hover0", true);
                if (null == index) index = this.arrowList.findIndexByProperty("hover1", true);

                if (null != index) {
                  const arrow = this.arrowList[index];

                  if (null != index) {
                    this.arrowList.splice(index, 1);
                    this.paint();

                    await CnHttpFactory.instance({
                      path: this.parentModel.getServiceResourcePath() + "/arrow/" + arrow.id
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
            if (this.activeArrow) {
              this.activeArrow = null;
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
              const point = this.getMousePoint(event);
              if (event.altKey) {
                // do nothing
              } else if (event.ctrlKey) {
                // left click creates an active arrow
                if (0 == event.button) this.createActiveArrow(point);
              } else if(event.shiftKey) {
                // do nothing
              } else {
                // left click or middle click grabs an arrow being hovered over
                if ([0,1].includes(event.button) && null == this.activeArrow) {
                  let hover = 0;
                  let index = this.arrowList.findIndexByProperty("hover0", true);
                  if (null == index) {
                    index = this.arrowList.findIndexByProperty("hover1", true);
                    hover = 1;
                  }

                  if (null != index) {
                    this.activeArrow = this.arrowList[index];
                    angular.extend(this.activeArrow, { grab0: 0 == hover, grab1: 1 == hover });
                    this.arrowList.splice(index, 1);
                    return;
                  }
                }
              }
            },

            onmouseup: (event) => {
              const point = this.getMousePoint(event);
              if (0 == event.button) { // left button
                if (null != this.activeArrow) this.saveActiveArrow();
              } else if (1 == event.button) { // middle button
                if (null != this.activeArrow) this.saveActiveArrow();
              } else if (2 == event.button) { // right button
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
                // highlight any arrow points under the cursor
                if (!event.altKey && !event.ctrlKey && !event.shiftKey && 0 < this.arrowList.length) {
                  this.highlightArrows(point);
                }
              } else if ("left" == button) {
                // move the active arrow's grab point
                if (null != this.activeArrow) {
                  if (this.activeArrow.grab0) {
                    this.moveActiveArrowStartPoint(point);
                  } else if (this.activeArrow.grab1) {
                    this.moveActiveArrowEndPoint(point);
                  }
                }
              } else if ("middle" == button) {
                // translate the active arrow
                if (null != this.activeArrow) {
                  if (this.activeArrow.grab0) {
                    this.translateActiveArrow(point.x - this.activeArrow.x0, point.y - this.activeArrow.y0);
                    return;
                  } if (this.activeArrow.grab1) {
                    this.translateActiveArrow(point.x - this.activeArrow.x1, point.y - this.activeArrow.y1);
                    return;
                  }
                }

                // if there is no arrow to translate then pan the image
                this.pan(-event.movementX, -event.movementY);
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
            const [imageResponse, arrowResponse] = await Promise.all([
              CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath(),
                data: { select: { column: "image" } },
              }).get(),

              CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath() + "/arrow",
                data: { modifier: { where: { column: "user_id", operator: "=", value: CnSession.user.id } } },
              }).get(),
            ]);

            this.image.src = imageResponse.data.image.data;

            this.arrowList = arrowResponse.data.reduce((list, arrow) => {
              list.push({
                id: arrow.id,
                x0: arrow.x0,
                y0: arrow.y0,
                x1: arrow.x1,
                y1: arrow.y1,
                hover0: false,
                hover1: false
              });
              return list;
            }, []);

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
