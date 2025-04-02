// Fix for the slider images, especially New Arrivals
document.addEventListener('DOMContentLoaded', function() {
    // Wait for the slider to be fully loaded
    setTimeout(function() {
        // Get all slides
        const slides = document.querySelectorAll('.slide');
        
        // Process each slide
        slides.forEach(function(slide) {
            // Get the title and image elements
            const titleElement = slide.querySelector('h5');
            const imgElement = slide.querySelector('.right img');
            
            if (titleElement && imgElement) {
                // Apply specific fix for special slides
                if (titleElement.textContent.trim() === 'New Arrivals' || 
                    titleElement.textContent.trim() === 'Selling Out Soon') {
                    
                    console.log('Fixing special slide image: ' + titleElement.textContent.trim());
                    
                    // Fix the right container
                    const rightContainer = slide.querySelector('.right');
                    if (rightContainer) {
                        rightContainer.style.display = 'flex';
                        rightContainer.style.justifyContent = 'center';
                        rightContainer.style.alignItems = 'center';
                        rightContainer.style.overflow = 'hidden';
                    }
                    
                    // Fix the image - use consistent styling for both New Arrivals and Selling Out Soon
                    imgElement.style.width = '100%';
                    imgElement.style.height = '100%';
                    imgElement.style.objectFit = 'cover';
                    imgElement.style.objectPosition = 'center';
                }
            }
        });
    }, 500); // Short delay to ensure all elements are rendered
});