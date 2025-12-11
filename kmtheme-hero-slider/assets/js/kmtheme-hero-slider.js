/**
 * KMTheme Hero Slider JavaScript
 * 
 * @package KMTheme_Hero_Slider
 * @author kminhhi
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    function initHeroSlider() {
        // Xử lý TẤT CẢ slider, không phân biệt class
        var sliders = document.querySelectorAll('.hero-slider');

        sliders.forEach(function (slider) {
            // Bỏ qua nếu là scroll-driven (sẽ được xử lý riêng)
            if (slider.classList.contains('hero-slider-scroll')) {
                return;
            }
            
            // Đảm bảo slider hiển thị
            slider.style.display = 'block';
            slider.style.visibility = 'visible';
            slider.style.opacity = '1';
            
            var slides = slider.querySelectorAll('.hero-slide');
            if (!slides.length) return;

            // Khởi tạo slide đầu tiên - đảm bảo hiển thị
            slides.forEach(function(slide, index) {
                if (index === 0) {
                    slide.classList.add('is-active');
                    slide.style.position = 'relative';
                    slide.style.opacity = '1';
                    slide.style.zIndex = '1';
                    slide.style.visibility = 'visible';
                } else {
                    slide.style.position = 'absolute';
                    slide.style.opacity = '0';
                    slide.style.zIndex = '0';
                    slide.style.visibility = 'hidden';
                }
            });

            // Auto-play (tùy chọn - có thể bật/tắt)
            // var autoPlayInterval = setInterval(function() {
            //     if (!animating) {
            //         var next = (current + 1) % slides.length;
            //         goTo(next);
            //     }
            // }, 5000);
        });
    }

    // Scroll-driven slider với GSAP ScrollTrigger
    function initScrollDrivenSlider() {
        if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
            return;
        }
        
        gsap.registerPlugin(ScrollTrigger);
        
        var scrollSliders = document.querySelectorAll('.hero-slider-scroll');
        
        scrollSliders.forEach(function(slider) {
            var slides = slider.querySelectorAll('.hero-slide');
            if (slides.length === 0) return;
            
            var totalSlides = slides.length;
            var currentSlide = 0;
            var sliderHeight = window.innerHeight;
            var isAnimating = false;
            var animationComplete = true; // Flag để đảm bảo animation hoàn thành
            var lastScrollTime = 0; // Thời gian scroll cuối cùng
            var scrollDelay = 50; // Delay giữa các lần scroll để tránh quá nhanh
            var scrollTriggerInstance = null; // Lưu instance của ScrollTrigger để control
            var animationDelay = 200; // Thời gian delay sau khi animation hoàn thành (ms) - giảm xuống để nhanh hơn
            
            // Khởi tạo slides
            slides.forEach(function(slide, index) {
                slide.style.position = 'absolute';
                slide.style.inset = '0';
                slide.style.width = '100%';
                slide.style.height = '100vh';
                
                // Reset tất cả animation classes
                slide.classList.remove('is-exiting-next', 'is-exiting-prev', 'is-entering-next', 'is-entering-prev');
                
                if (index === 0) {
                    slide.classList.add('is-active');
                    slide.style.opacity = '1';
                    slide.style.zIndex = '1';
                    slide.style.visibility = 'visible';
                    slide.style.transition = '';
                } else {
                    slide.classList.remove('is-active');
                    slide.style.opacity = '0';
                    slide.style.zIndex = '0';
                    slide.style.visibility = 'hidden';
                    slide.style.transition = '';
                }
            });
            
            // Tính toán end point: giảm độ dài scroll - mỗi slide chỉ chiếm 50vh thay vì 100vh
            var endPoint = sliderHeight * totalSlides * 0.5; // Giảm 50%
            
            // Hàm chuyển slide - đồng bộ animation với nút bấm
            function changeSlide(newSlideIndex, direction) {
                // Kiểm tra nghiêm ngặt: chỉ cho phép chuyển slide nếu animation đã hoàn thành
                if (isAnimating || !animationComplete) {
                    // Nếu đang có animation, lưu slide đang chờ
                    if (newSlideIndex !== currentSlide && newSlideIndex >= 0 && newSlideIndex < totalSlides) {
                        pendingSlide = newSlideIndex;
                    }
                    return; // Dừng lại ngay lập tức nếu animation đang chạy
                }
                
                // Kiểm tra nếu slide mới giống slide hiện tại
                if (newSlideIndex === currentSlide || newSlideIndex < 0 || newSlideIndex >= totalSlides) {
                    return;
                }
                
                // Kiểm tra thời gian scroll để tránh quá nhanh
                var now = Date.now();
                if (now - lastScrollTime < scrollDelay) {
                    return;
                }
                lastScrollTime = now;
                
                // Đánh dấu animation bắt đầu - KHÔNG cho phép animation khác chạy
                isAnimating = true;
                animationComplete = false; // Đánh dấu animation đang chạy
                lockedScrollPosition = window.scrollY; // Lưu vị trí scroll để khóa
                // Xác định direction dựa trên slide index
                // Nếu newSlideIndex > currentSlide thì là next, ngược lại là prev
                var actualDirection = newSlideIndex > currentSlide ? 'next' : 'prev';
                // Nhưng luôn dùng animation 'next' để đồng bộ
                direction = 'next';
                
                var currentSlideEl = slides[currentSlide];
                var nextSlideEl = slides[newSlideIndex];
                
                // Reset animation classes trước
                if (currentSlideEl) {
                    currentSlideEl.classList.remove('is-exiting-next', 'is-exiting-prev', 'is-entering-next', 'is-entering-prev');
                }
                if (nextSlideEl) {
                    nextSlideEl.classList.remove('is-exiting-next', 'is-exiting-prev', 'is-entering-next', 'is-entering-prev');
                }
                
                // Ẩn slide cũ với animation giống nút bấm
                if (currentSlideEl) {
                    currentSlideEl.classList.remove('is-active');
                    currentSlideEl.style.zIndex = '0';
                    currentSlideEl.style.opacity = '1'; // Giữ opacity để animation chạy
                    currentSlideEl.classList.add('is-exiting-' + direction);
                }
                
                // Hiển thị slide mới với animation giống nút bấm
                if (nextSlideEl) {
                    nextSlideEl.style.position = 'absolute';
                    nextSlideEl.style.inset = '0';
                    nextSlideEl.style.zIndex = '2';
                    nextSlideEl.style.opacity = '0';
                    nextSlideEl.style.visibility = 'visible'; // Đảm bảo visible để animation chạy
                    nextSlideEl.classList.add('is-entering-' + direction);
                    
                    // Hiển thị slide mới với animation - đợi một chút để đảm bảo CSS animation được trigger
                    // Không dùng transition opacity vì đã có CSS animation
                    requestAnimationFrame(function() {
                        requestAnimationFrame(function() {
                            nextSlideEl.style.opacity = '1';
                        });
                    });
                    
                    // Hoàn thành animation với duration mới
                    setTimeout(function() {
                        // Kiểm tra lại để đảm bảo không có animation khác đã bắt đầu
                        if (!isAnimating) {
                            return; // Nếu đã có animation khác, không làm gì
                        }
                        
                        if (currentSlideEl) {
                            currentSlideEl.classList.remove('is-exiting-next', 'is-exiting-prev');
                            currentSlideEl.style.opacity = '0';
                            currentSlideEl.style.zIndex = '0';
                            currentSlideEl.style.visibility = 'hidden';
                        }
                        
                        nextSlideEl.classList.remove('is-entering-next', 'is-entering-prev');
                        nextSlideEl.style.zIndex = '1';
                        nextSlideEl.style.visibility = 'visible';
                        nextSlideEl.classList.add('is-active');
                        
                        // Reset tất cả slides
                        slides.forEach(function(s, i) {
                            if (i === newSlideIndex) {
                                s.style.zIndex = '1';
                                s.style.opacity = '1';
                                s.style.visibility = 'visible';
                            } else {
                                s.style.zIndex = '0';
                                s.style.opacity = '0';
                                s.style.visibility = 'hidden';
                                s.classList.remove('is-active');
                            }
                        });
                        
                        currentSlide = newSlideIndex;
                        
                        // CHỈ reset flags sau khi tất cả đã hoàn thành + thêm delay
                        // Đợi thêm một chút để người dùng nhìn thấy slide mới
                        setTimeout(function() {
                            isAnimating = false;
                            animationComplete = true; // Đánh dấu animation đã hoàn thành
                            lockedScrollPosition = null; // Mở khóa scroll khi animation hoàn thành
                            
                            // Kiểm tra nếu có slide đang chờ sau khi animation hoàn thành
                            if (pendingSlide !== null && pendingSlide !== currentSlide && pendingSlide >= 0 && pendingSlide < totalSlides) {
                                // Chạy animation tiếp theo sau khi delay
                                setTimeout(function() {
                                    if (animationComplete && !isAnimating) {
                                        changeSlide(pendingSlide, 'next');
                                        pendingSlide = null;
                                    }
                                }, 50);
                            }
                        }, animationDelay); // Thêm delay sau khi animation hoàn thành
                    }, 1000); // Đồng bộ với duration mới (1s)
                }
            }
            
            // Lưu trữ slide đang chờ khi animation đang chạy
            var pendingSlide = null;
            var lockedScrollPosition = null; // Lưu vị trí scroll khi bị khóa
            
            // Tạo ScrollTrigger để pin slider và control slides
            scrollTriggerInstance = ScrollTrigger.create({
                trigger: slider,
                start: 'top top',
                end: '+=' + endPoint,
                pin: true,
                anticipatePin: 1,
                onUpdate: function(self) {
                    // Nếu animation đang chạy, khóa scroll và không cho phép chuyển slide
                    if (isAnimating || !animationComplete) {
                        // Lưu vị trí scroll hiện tại để khóa
                        if (lockedScrollPosition === null) {
                            lockedScrollPosition = window.scrollY;
                        }
                        
                        // Giữ scroll position cố định
                        if (Math.abs(window.scrollY - lockedScrollPosition) > 5) {
                            window.scrollTo(0, lockedScrollPosition);
                        }
                        
                        var progress = self.progress;
                        var slideProgress = progress * totalSlides;
                        var newSlide = Math.floor(slideProgress);
                        
                        if (newSlide >= totalSlides) {
                            newSlide = totalSlides - 1;
                        }
                        if (newSlide < 0) {
                            newSlide = 0;
                        }
                        
                        // Lưu slide đang chờ nếu khác với slide hiện tại
                        if (newSlide !== currentSlide) {
                            pendingSlide = newSlide;
                        }
                        
                        return; // Dừng lại, không chuyển slide khi animation đang chạy
                    }
                    
                    // Mở khóa scroll khi animation hoàn thành
                    lockedScrollPosition = null;
                    
                    var progress = self.progress;
                    
                    // Tính slide index dựa trên progress
                    // Mỗi slide chiếm 1/totalSlides của progress
                    var slideProgress = progress * totalSlides;
                    var newSlide = Math.floor(slideProgress);
                    
                    // Đảm bảo index hợp lệ
                    if (newSlide >= totalSlides) {
                        newSlide = totalSlides - 1;
                    }
                    if (newSlide < 0) {
                        newSlide = 0;
                    }
                    
                    // Chuyển slide nếu cần và animation đã hoàn thành
                    if (newSlide !== currentSlide && !isAnimating && animationComplete) {
                        changeSlide(newSlide, 'next');
                    }
                    
                    // Kiểm tra nếu có slide đang chờ sau khi animation hoàn thành
                    if (pendingSlide !== null && pendingSlide !== currentSlide && animationComplete) {
                        changeSlide(pendingSlide, 'next');
                        pendingSlide = null;
                    }
                }
            });
            
            // Thêm event listener để chặn scroll khi animation đang chạy
            var scrollHandler = function(e) {
                if (isAnimating || !animationComplete) {
                    // Kiểm tra nếu đang ở slide đầu (scroll lên) hoặc slide cuối (scroll xuống)
                    var isScrollingUp = e.deltaY < 0 || (e.type === 'wheel' && e.deltaY < 0);
                    var isScrollingDown = e.deltaY > 0 || (e.type === 'wheel' && e.deltaY > 0);
                    
                    // Nếu scroll lên và đang ở slide đầu, hoặc scroll xuống và đang ở slide cuối
                    if ((isScrollingUp && currentSlide === 0) || (isScrollingDown && currentSlide === totalSlides - 1)) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
            };
            
            // Thêm event listeners
            window.addEventListener('wheel', scrollHandler, { passive: false });
            window.addEventListener('touchmove', scrollHandler, { passive: false });
            
            // Cleanup khi slider bị remove
            slider.addEventListener('remove', function() {
                window.removeEventListener('wheel', scrollHandler);
                window.removeEventListener('touchmove', scrollHandler);
            });
        });
    }
    
    // Khởi tạo khi DOM ready
    $(document).ready(function() {
        // Đảm bảo tất cả slider hiển thị trước khi khởi tạo
        var allSliders = document.querySelectorAll('.hero-slider');
        allSliders.forEach(function(slider) {
            // Đảm bảo slider container hiển thị
            slider.style.display = 'block';
            slider.style.visibility = 'visible';
            slider.style.opacity = '1';
            slider.style.minHeight = '100vh';
            
            // Đảm bảo hero-slides hiển thị
            var heroSlides = slider.querySelector('.hero-slides');
            if (heroSlides) {
                heroSlides.style.display = 'block';
                heroSlides.style.visibility = 'visible';
            }
            
            // Đảm bảo slide đầu tiên hiển thị
            var firstSlide = slider.querySelector('.hero-slide:first-child');
            if (firstSlide) {
                firstSlide.style.opacity = '1';
                firstSlide.style.visibility = 'visible';
                firstSlide.style.zIndex = '1';
                firstSlide.style.position = 'relative';
                firstSlide.classList.add('is-active');
                
                // Đảm bảo nội dung trong slide hiển thị
                var slideInner = firstSlide.querySelector('.hero-slide-inner');
                if (slideInner) {
                    slideInner.style.display = 'flex';
                    slideInner.style.visibility = 'visible';
                }
            }
        });
        
        initHeroSlider();
        
        // Khởi tạo scroll-driven slider sau một chút để đảm bảo GSAP đã load
        setTimeout(function() {
            initScrollDrivenSlider();
        }, 100);
    });

    // Khởi tạo lại nếu có AJAX load content (cho Flatsome PJAX)
    $(document).on('flatsome-pjax-loaded', function() {
        initHeroSlider();
        setTimeout(function() {
            if (typeof ScrollTrigger !== 'undefined') {
                ScrollTrigger.refresh();
            }
            initScrollDrivenSlider();
        }, 100);
    });

})(jQuery);
