/**
 * KMTheme Gallery JavaScript
 */

(function($) {
    'use strict';
    
    // Lightbox functionality
    function initGalleryLightbox() {
        // Create lightbox HTML
        if ($('.gallery-lightbox').length === 0) {
            $('body').append(`
                <div class="gallery-lightbox">
                    <button class="gallery-lightbox-close"><i class="fas fa-times"></i></button>
                    <button class="gallery-lightbox-prev"><i class="fas fa-chevron-left"></i></button>
                    <button class="gallery-lightbox-next"><i class="fas fa-chevron-right"></i></button>
                    <div class="gallery-lightbox-content">
                        <img src="" alt="">
                    </div>
                </div>
            `);
        }
        
        var lightbox = $('.gallery-lightbox');
        var lightboxImg = lightbox.find('img');
        var currentIndex = 0;
        var images = [];
        
        // Collect all gallery images
        function collectImages() {
            images = [];
            $('.gallery-link').each(function() {
                images.push({
                    src: $(this).attr('href'),
                    alt: $(this).find('img').attr('alt')
                });
            });
        }
        
        // Open lightbox
        $('.gallery-link').on('click', function(e) {
            e.preventDefault();
            collectImages();
            
            var clickedSrc = $(this).attr('href');
            currentIndex = images.findIndex(function(img) {
                return img.src === clickedSrc;
            });
            
            if (currentIndex === -1) currentIndex = 0;
            
            updateLightbox();
            lightbox.addClass('active');
            $('body').css('overflow', 'hidden');
        });
        
        // Update lightbox image
        function updateLightbox() {
            if (images.length > 0 && images[currentIndex]) {
                lightboxImg.attr('src', images[currentIndex].src);
                lightboxImg.attr('alt', images[currentIndex].alt);
            }
        }
        
        // Close lightbox
        $('.gallery-lightbox-close').on('click', function() {
            lightbox.removeClass('active');
            $('body').css('overflow', '');
        });
        
        // Close on background click
        lightbox.on('click', function(e) {
            if ($(e.target).hasClass('gallery-lightbox')) {
                lightbox.removeClass('active');
                $('body').css('overflow', '');
            }
        });
        
        // Next image
        $('.gallery-lightbox-next').on('click', function(e) {
            e.stopPropagation();
            currentIndex = (currentIndex + 1) % images.length;
            updateLightbox();
        });
        
        // Previous image
        $('.gallery-lightbox-prev').on('click', function(e) {
            e.stopPropagation();
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateLightbox();
        });
        
        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (lightbox.hasClass('active')) {
                if (e.key === 'Escape') {
                    lightbox.removeClass('active');
                    $('body').css('overflow', '');
                } else if (e.key === 'ArrowRight') {
                    currentIndex = (currentIndex + 1) % images.length;
                    updateLightbox();
                } else if (e.key === 'ArrowLeft') {
                    currentIndex = (currentIndex - 1 + images.length) % images.length;
                    updateLightbox();
                }
            }
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initGalleryLightbox();
    });
    
    // Re-initialize for AJAX loaded content (Flatsome PJAX)
    $(document).on('flatsome-pjax-loaded', function() {
        initGalleryLightbox();
    });
    
})(jQuery);

