// Modern Script for DDD Symfony Guide

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Navigation Toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('show');
            menuToggle.setAttribute('aria-expanded',
                menuToggle.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
            );
        });
    }

    // Scroll to Top Button
    const scrollToTopBtn = document.querySelector('.scroll-to-top');

    if (scrollToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });

        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');

            if (href !== '#' && href.startsWith('#')) {
                e.preventDefault();

                const targetElement = document.querySelector(this.getAttribute('href'));
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });

                    // Update URL without page jump
                    history.pushState(null, null, href);
                }
            }
        });
    });

    // Code highlighting enhancement
    document.querySelectorAll('pre code').forEach((block) => {
        // Add copy button
        const pre = block.parentNode;
        const copyButton = document.createElement('button');
        copyButton.className = 'copy-button';
        copyButton.textContent = 'Kopírovat';

        copyButton.addEventListener('click', () => {
            const code = block.innerText;
            navigator.clipboard.writeText(code).then(() => {
                copyButton.textContent = 'Zkopírováno!';
                setTimeout(() => {
                    copyButton.textContent = 'Kopírovat';
                }, 2000);
            });
        });

        pre.appendChild(copyButton);
    });

    // Table responsiveness
    document.querySelectorAll('table').forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // Add active class to current nav item
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

    // Placeholder for future enhancements

    // Add fade-in animation to elements as they scroll into view
    const fadeElements = document.querySelectorAll('.card, .concept-box, .warning-box, .example-box');

    const fadeInObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                fadeInObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    fadeElements.forEach(element => {
        fadeInObserver.observe(element);
    });
});
