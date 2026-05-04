/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
        'jquery'
    ],
    function ($) {

        /**
         * Replacer object. Used for replace main product image in the gallery with corresponding option image
         * from custom options
         */
        return {
            /**
             * Candidates for replacement (images)
             */
            candidates: {},

            /**
             * Adds new candidate to the candidates
             * @param image
             * @param sortOrder
             */
            addCandidate: function (image, sortOrder) {
                this.candidates[sortOrder] = image;
            },

            removeCandidate: function (sortOrder) {
                if (typeof this.candidates[sortOrder] == 'undefined') {
                    return;
                }
                delete this.candidates[sortOrder];
            },

            getCandidates: function () {
                return this.candidates;
            },

            /**
             * Find most suitable image-candidate for replacement using sort order (option/value)
             * and return it (image)
             * @returns image
             */
            getLastCandidate: function () {
                var lastCandidate,
                    prevKey = 0;
                for (var key in this.candidates) {
                    if (!this.candidates.hasOwnProperty(key)) {
                        continue;
                    }
                    if (parseInt(prevKey) < parseInt(key)) {
                        lastCandidate = this.candidates[key];
                    }
                    prevKey = key;
                }

                return lastCandidate;
            },

            /**
             * Main method: do replacement main product image with last candidate
             */
            replace: function () {
                var image = this.getLastCandidate(),
                    self = this,
                    gallery,
                    galleryCurrentImages;

                if (!image || typeof image == 'undefined') {
                    return;
                }

                $.when(self.waitLoadGallery()).then(function (result) {
                    galleryCurrentImages = result.returnCurrentImages();
                    self.fillImageWithDefaultData(image);
                    galleryCurrentImages.forEach(function (e, i) {
                        if (typeof e.is_custom != 'undefined' && e.is_custom == 1) {
                            galleryCurrentImages.splice(i, 1);
                            return;
                        }
                        if (e.isMain == true) {
                            e.isMain = false;
                            e.position += 1;
                        }
                    });

                    galleryCurrentImages.unshift(image);
                    result.updateData(galleryCurrentImages);
                    result.first();
                }, function (error) {
                    console.log(error);
                });
            },

            /**
             * Important method: refresh the images gallery before we make changes (replacement)
             * We should not store old images in the gallery
             *
             * @see mage.optionAdditionalImages._elementChange
             */
            forceRefresh: function () {
                var image = this.getLastCandidate(),
                    self = this,
                    gallery,
                    galleryCurrentImages;

                $.when(self.waitLoadGallery()).then(function (result) {
                    galleryCurrentImages = result.returnCurrentImages();
                    galleryCurrentImages.forEach(function (e, i) {
                        if (typeof e.is_custom != 'undefined' && e.is_custom == 1) {
                            galleryCurrentImages.splice(i, 1);
                            return;
                        }
                        if (image && e.isMain == true) {
                            e.isMain = false;
                            e.position += 1;
                        }
                    });
                    if (image) {
                        self.fillImageWithDefaultData(image);
                        galleryCurrentImages.unshift(image);
                    }
                    result.updateData(galleryCurrentImages);
                }, function (error) {
                    console.log(error);
                });
            },

            /**
             * Add default data to the image object created from our candidate
             *
             * @param image
             * @returns {*}
             */
            fillImageWithDefaultData: function (image) {
                image.caption = image.label;
                image.i = 1;
                image.isMain = true;
                image.position = 1;
                image.is_custom = 1;

                return image;
            },

            /**
             * Resolve promise when product gallery is loaded.
             *
             * @returns {*}
             */
            waitLoadGallery: function () {
                var dfd = $.Deferred();

                var timer = setInterval(function () {
                    gallery = $('[data-gallery-role=gallery-placeholder]').data('gallery');
                    if (typeof gallery != 'undefined') {
                        clearInterval(timer);
                        dfd.resolve(gallery);
                    }
                }, 500);

                return dfd.promise();
            }
        }
    }
);