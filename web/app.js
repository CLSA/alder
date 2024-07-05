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
  "CnHttpFactory",
  "$timeout",
  function (CnHttpFactory, $timeout) {
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

        activeArrow: null,

        paint: function() {
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

          this.context.beginPath();
          this.context.lineWidth = "2";
          this.context.rect(0, 0, this.view.width, this.view.height);
          this.context.strokeStyle = "red";
          this.context.stroke();
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

          this.paint();
        },

        pan: function(dx, dy) {
          let x = this.imageTransform.x + dx * this.icRatio / this.imageTransform.scale;
          let y = this.imageTransform.y + dy * this.icRatio / this.imageTransform.scale;

          angular.extend(this.imageTransform, {
            x: this.bounds.x.min > x ? this.bounds.x.min : this.bounds.x.max < x ? this.bounds.x.max : x,
            y: this.bounds.x.min > y ? this.bounds.x.min : this.bounds.y.max < y ? this.bounds.y.max : y,
          });

          this.paint();
        },

        reset: function() {
          this.imageTransform = { x: 0, y: 0, b: 1, c: 1, scale: 1 };
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

        drawArrow: function(x, y) {
          this.activeArrow = {
            
          };
          console.log("start line at " + x + ", " + y);
        },

        endArrow: function(x, y) {
          console.log("end line at " + x + ", " + y);
        },

        drawElipse: function() {
        },

        drawSquare: function() {
        },

        buttonClick: function(button, x, y, down) {
          if ("left" == button) {
            down ? this.drawArrow(x, y) : this.endArrow(x,y);
          }
        },

        mouseMove: function(button, x, y, dx, dy) {
          if ("left" == button) {
          } else if ("middle" == button) {
            this.pan(-dx, -dy);
          } else if ("right" == button) {
            this.windowLevel(dx, -dy);
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
          window.addEventListener("keydown", (event) => {
            if (this.canvas.parentNode.matches(":hover")) {
              if ("Escape" == event.key) {
                this.reset();
              }
              event.preventDefault();
              event.stopPropagation();
            }
          });

          // track mouse operations
          angular.extend(this.canvas, {
            oncontextmenu: (event) => {
              event.preventDefault();
              event.stopPropagation();
            },

            ondblclick: (event) => {
              console.log(event);
            },

            onmousedown: (event) => {
              const rect = this.canvas.getBoundingClientRect();
              let x = event.clientX - rect.left;
              let y = event.clientY - rect.top;
              if (0 == event.button) this.buttonClick("left", x, y, true);
              else if (1 == event.button) this.buttonClick("middle", x, y, true);
              else if (2 == event.button) this.buttonClick("right", x, y, true);
            },

            onmouseup: (event) => {
              const rect = this.canvas.getBoundingClientRect();
              let x = event.clientX - rect.left;
              let y = event.clientY - rect.top;
              if (0 == event.button) this.buttonClick("left", x, y, false);
              else if (1 == event.button) this.buttonClick("middle", x, y, false);
              else if (2 == event.button) this.buttonClick("right", x, y, false);
            },

            onwheel: (event) => {
              event.preventDefault();
              event.stopPropagation();
              const rect = this.canvas.getBoundingClientRect();
              this.scale(
                event.clientX-rect.left,
                event.clientY-rect.top,
                0 < event.wheelDelta
              );
            },

            onmousemove: (event) => {
              let button = null;
              if (1 == event.buttons) button = "left";
              else if (4 == event.buttons) button = "middle";
              else if (2 == event.buttons) button = "right";

              if (null != button) {
                const rect = this.canvas.getBoundingClientRect();
                this.mouseMove(
                  button,
                  event.clientX-rect.left,
                  event.clientY-rect.top,
                  event.movementX,
                  event.movementY
                );
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
            }

            const response = await CnHttpFactory.instance({
              path: this.parentModel.getServiceResourcePath(),
              data: { select: { column: "image" } }
            }).get();
            this.image.src = response.data.image.data;
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
