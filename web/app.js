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
        imageScale: null,

        createCanvas: function(width, height) {
          const ratio = window.devicePixelRatio;
          const canvas = document.getElementById("canvas");
          const canvasRect = canvas.getBoundingClientRect();
          canvas.width = canvasRect.width * ratio;
          canvas.height = canvasRect.height * ratio;
          canvas.getContext("2d").scale(ratio, ratio);
          return canvas;
        },

        drawImage: function() {
          const canvasRect = this.canvas.getBoundingClientRect();
          this.canvas.width = canvasRect.width;
          this.canvas.height = canvasRect.height;
          this.canvasRatio = this.canvas.width/this.canvas.height;
          this.imageRatio = this.image.width/this.image.height;

          // clear background by filling with black
          this.context.rect(0, 0, this.canvas.width, this.canvas.height);
          this.context.fillStyle = "black";
          this.context.fill();

          // TODO: find way to use whole canvas when zooming

          // now add image
          this.context.drawImage(
            this.image,
            // scaled x and y
            this.imageScale.x, this.imageScale.y,
            // scalled width and height
            this.image.width / this.imageScale.scale, this.image.height / this.imageScale.scale,
            // origin
            0, 0,
            // maximum width and height
            1 > this.imageRatio ? this.canvas.width / this.canvasRatio * this.imageRatio : this.canvas.width,
            1 < this.imageRatio ? this.canvas.height * this.canvasRatio / this.imageRatio : this.canvas.height
          );
        },

        scale: function(x, y, inward) {
          // TODO:
          // Currently this assumes that the image is maximized by width.
          // When the image is maximized by height then the height/width details below must be swapped.

          // zoom in by 1/32nd of the image width
          const factor = (inward ? 1 : -1) * this.image.width / 32;

          // determine the new scaling factor
          let width = this.image.width / this.imageScale.scale - factor;
          const newScale = this.image.width / width;

          // restrict to scalling boundaries (1x to 10x)
          if (1 > newScale || 10 < newScale) return;
          
          // apply the new scale and redraw the image
          this.imageScale.scale = newScale;
          const dx = x * factor / this.canvas.width;
          const dy = y * factor / this.canvasRatio / this.canvas.height;
          this.imageScale.x += dx;
          if (1 == this.imageScale.scale || 0 > this.imageScale.x) this.imageScale.x = 0;
          this.imageScale.y += dy;
          if (1 == this.imageScale.scale || 0 > this.imageScale.y) this.imageScale.y = 0;
          this.drawImage();
        },

        pan: function(x, y) {
        },

        windowLevel: function(window, level) {
        },

        drawLine: function() {
        },

        drawCircle: function() {
        },

        drawSquare: function() {
        },

        buttonClick: function(button, down) {
        },

        mouseMove: function(button, x, y, mx, my) {
        },

        createEventListeners: function() {
          // redraw the image when the window is resized
          // TODO: this may not resize at the very last 50 ms.  Try figuring out how to improve it
          var resizeWait = false;
          window.addEventListener("resize", () => {
            if (!resizeWait) {
              this.drawImage();
              resizeWait = true;
              $timeout(() => (resizeWait = false), 50);
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
            this.context.font = "24px Arial";
            this.context.fillText("Loading image...", 20, 40);
            this.image = new Image();
            this.image.onload = () => {
              this.imageScale = { x: 0, y: 0, scale: 1 };
              this.drawImage();
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
