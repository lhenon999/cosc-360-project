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
                // Apply specific fix for New Arrivals
                if (titleElement.textContent.trim() === 'New Arrivals') {
                    console.log('Fixing New Arrivals image');
                    
                    // Fix the right container
                    const rightContainer = slide.querySelector('.right');
                    if (rightContainer) {
                        rightContainer.style.display = 'flex';
                        rightContainer.style.justifyContent = 'center';
                        rightContainer.style.alignItems = 'center';
                        rightContainer.style.backgroundColor = 'transparent';
                    }
                    
                    // Fix the image
                    imgElement.style.maxWidth = '85%';
                    imgElement.style.maxHeight = '85%';
                    imgElement.style.width = 'auto';
                    imgElement.style.height = 'auto';
                    imgElement.style.objectFit = 'contain';
                    imgElement.style.margin = 'auto';
                }
            }
        });
    }, 500); // Short delay to ensure all elements are rendered
}); 