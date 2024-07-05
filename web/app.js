"use strict";

var cenozo = angular.module("cenozo");

cenozo.controller("HeaderCtrl", [
  "$scope",
  "CnBaseHeader",
  function ($scope, CnBaseHeader) {
    // copanY all properties from the base header
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
          b: { min: 0.5, max: 2.0 },
          c: { min: 1.0, max: 10.0 },
        },

        createCanvas: function(width, height) {
          const ratio = window.devicePixelRatio;
          const canvas = document.getElementById("canvas");
          const canvasRect = canvas.getBoundingClientRect();
          canvas.width = canvasRect.width * ratio;
          canvas.height = canvasRect.height * ratio;
          canvas.getContext("2d").scale(ratio, ratio);
          return canvas;
        },

        paint: function() {
          // recalculate the canvas size based on the bounding client
          const canvasRect = this.canvas.getBoundingClientRect();
          this.canvas.width = canvasRect.width;
          this.canvas.height = canvasRect.height;
          this.canvasRatio = this.canvas.width/this.canvas.height;
          this.heightBased = this.canvasRatio > this.imageRatio;

          // scale the image based on the current transform
          this.context.scale(this.imageTransform.scale, this.imageTransform.scale);

          // clear background by filling with black
          this.context.rect(0, 0, this.canvas.width, this.canvas.height);
          this.context.fillStyle = "black";
          this.context.fill();

          this.context.filter =
            "brightness(" + (100*this.imageTransform.b) + "%) " +
            "contrast(" + (100*this.imageTransform.c) + "%)";

          this.context.drawImage(
            this.image,

            // transformed origin
            this.imageTransform.x + this.imageTransform.panX / this.imageTransform.scale,
            this.imageTransform.y + this.imageTransform.panY / this.imageTransform.scale,

            // image size
            this.image.width, this.image.height,

            // canvas origin
            0, 0,

            // image drawn on canvas size (removing any extra space)
            this.canvas.width - (
              this.heightBased ?
              this.canvas.width - this.canvas.height*this.imageRatio :
              0
            ),
            this.canvas.height - (
              this.heightBased ?
              0 :
              this.canvas.height - this.canvas.width/this.imageRatio
            )
          );
        },

        scale: function(cx, cy, inward) {
          // we either use the image height or width, depending on how it fits on the canvas
          let imageMeasure = this.heightBased ? this.image.height : this.image.width;

          // zoom in by 1/32nd of the image width
          let factor = (inward ? 1 : -1) * imageMeasure / 32;

          // determine the new scaling factor
          let newScale = imageMeasure / (imageMeasure / this.imageTransform.scale - factor);

          // restrict to scalling boundaries (1x to 10x)
          if (1 > newScale || 10 < newScale) return;

          // determine the new transform coordinates
          let x = 0, y = 0;
          if (1 < this.imageTransform.scale) {
            x = this.imageTransform.x + cx * factor / this.canvas.width * (this.heightBased?this.canvasRatio:1);
            y = this.imageTransform.y + cy * factor / this.canvas.height / (this.heightBased?1:this.canvasRatio);
          }

          // apply the new scale and redraw the image
          angular.extend(this.imageTransform, {
            x: 0 > x ? 0 : x,
            y: 0 > y ? 0 : y,
            scale: newScale,
          });

          this.paint();
        },

        pan: function(dx, dy) {
          let ratio = (
            this.heightBased ?
            this.image.height/this.canvas.height :
            this.image.width/this.canvas.width
          );
          let x = this.imageTransform.x + dx * ratio / this.imageTransform.scale;
          let y = this.imageTransform.y + dy * ratio / this.imageTransform.scale;

          angular.extend(this.imageTransform, {
            x: 0 > x ? 0 : x,
            y: 0 > y ? 0 : y,
          });

          this.paint();
        },

        reset: function() {
          this.imageTransform = { x: 0, y: 0, panX: 0, panY: 0, b: 1, c: 1, scale: 1 };
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

        drawLine: function() {
        },

        drawCircle: function() {
        },

        drawSquare: function() {
        },

        buttonClick: function(button, down) {
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
              if ("r" == event.key) {
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

            onmousedown: (event) => {
              if (0 == event.button) this.buttonClick("left", true);
              else if (1 == event.button) this.buttonClick("middle", true);
              else if (2 == event.button) this.buttonClick("right", true);
            },

            onmouseup: (event) => {
              if (0 == event.button) this.buttonClick("left", false);
              else if (1 == event.button) this.buttonClick("middle", false);
              else if (2 == event.button) this.buttonClick("right", false);
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
            this.canvas = this.createCanvas();

            this.context = this.canvas.getContext("2d");
            this.context.font = "24panX Arial";
            this.context.fillText("Loading image...", 20, 40);

            this.image = new Image();
            this.image.onload = () => {
              this.imageRatio = this.image.width/this.image.height;
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
