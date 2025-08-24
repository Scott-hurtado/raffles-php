/**
 * Index Page JavaScript
 * 
 * Handles all functionality for the main index page including:
 * - Countdown timer
 * - Rifas slider
 * - Ticket selection
 * - Contact form
 * - Package purchasing
 */

class IndexController {
    constructor() {
        this.selectedTickets = [];
        this.takenTickets = [];
        this.ticketPrice = 5.00;
        this.countdownInterval = null;
        
        this.init();
    }

    /**
     * Initialize all index page functionality
     */
    init() {
        this.initializeNavbar();
        this.initCountdown();
        this.initRifasSlider();
        this.initTicketSelection();
    }

    /**
     * Initialize navbar if available
     */
    initializeNavbar() {
        if (typeof NavbarController !== 'undefined') {
            new NavbarController();
        }
    }

    /**
     * Initialize countdown timer
     */
    initCountdown() {
        // Set target date (7 days from now - you can change this)
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + 7);
        targetDate.setHours(targetDate.getHours() + 15);
        targetDate.setMinutes(targetDate.getMinutes() + 49);
        targetDate.setSeconds(targetDate.getSeconds() + 28);

        const updateCountdown = () => {
            const now = new Date().getTime();
            const timeLeft = targetDate.getTime() - now;

            if (timeLeft <= 0) {
                this.handleCountdownExpired();
                return;
            }

            const timeUnits = this.calculateTimeUnits(timeLeft);
            this.updateCountdownDisplay(timeUnits);
            this.updateProgressCircles(timeUnits);
        };

        // Update countdown every second
        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }

    /**
     * Calculate time units from milliseconds
     */
    calculateTimeUnits(timeLeft) {
        return {
            days: Math.floor(timeLeft / (1000 * 60 * 60 * 24)),
            hours: Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
            minutes: Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60)),
            seconds: Math.floor((timeLeft % (1000 * 60)) / 1000)
        };
    }

    /**
     * Update countdown display elements
     */
    updateCountdownDisplay(timeUnits) {
        const elements = {
            days: document.getElementById('days'),
            hours: document.getElementById('hours'),
            minutes: document.getElementById('minutes'),
            seconds: document.getElementById('seconds')
        };

        Object.keys(elements).forEach(key => {
            if (elements[key]) {
                elements[key].textContent = timeUnits[key].toString().padStart(2, '0');
            }
        });
    }

    /**
     * Update progress circles
     */
    updateProgressCircles(timeUnits) {
        this.updateProgress('days-progress', timeUnits.days / 30); // Assuming max 30 days
        this.updateProgress('hours-progress', timeUnits.hours / 24);
        this.updateProgress('minutes-progress', timeUnits.minutes / 60);
        this.updateProgress('seconds-progress', timeUnits.seconds / 60);
    }

    /**
     * Update individual progress circle
     */
    updateProgress(elementId, progress) {
        const circle = document.getElementById(elementId);
        if (!circle) return;

        const circumference = 2 * Math.PI * 54; // radius = 54
        const offset = circumference - (progress * circumference);
        
        circle.style.strokeDasharray = circumference;
        circle.style.strokeDashoffset = offset;
    }

    /**
     * Handle countdown expiration
     */
    handleCountdownExpired() {
        const elements = ['days', 'hours', 'minutes', 'seconds'];
        const progressElements = ['days-progress', 'hours-progress', 'minutes-progress', 'seconds-progress'];
        
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.textContent = '00';
        });

        progressElements.forEach(id => {
            this.updateProgress(id, 0);
        });

        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    }

    /**
     * Initialize rifas slider
     */
    initRifasSlider() {
        const rifasData = [
            {
                id: 1,
                name: "iPhone 15 Pro Max",
                date: "15 AGO • 20:00",
                price: "$50.00",
                image: "https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 2,
                name: "PlayStation 5 + Juegos",
                date: "20 AGO • 18:30",
                price: "$30.00",
                image: "https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 3,
                name: "MacBook Air M2",
                date: "25 AGO • 19:00",
                price: "$75.00",
                image: "https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 4,
                name: "Tesla Model 3",
                date: "30 AGO • 21:00",
                price: "$500.00",
                image: "https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 5,
                name: "Apple Watch Ultra",
                date: "05 SEP • 17:00",
                price: "$40.00",
                image: "https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 6,
                name: "Samsung Galaxy S24",
                date: "10 SEP • 19:30",
                price: "$45.00",
                image: "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 7,
                name: "Nintendo Switch OLED",
                date: "12 SEP • 16:00",
                price: "$35.00",
                image: "https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=150&h=150&fit=crop&crop=center"
            },
            {
                id: 8,
                name: "iPad Pro 12.9\"",
                date: "18 SEP • 20:30",
                price: "$60.00",
                image: "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=150&h=150&fit=crop&crop=center"
            }
        ];

        const slider = document.getElementById('rifasSlider');
        if (!slider) return;

        // Add cards twice for seamless infinite scroll
        const allCards = [...rifasData, ...rifasData];
        slider.innerHTML = allCards.map(rifa => this.createRifaCard(rifa)).join('');
    }

    /**
     * Create HTML for a rifa card
     */
    createRifaCard(rifa) {
        return `
            <div class="rifa-card" data-id="${rifa.id}">
                <img src="${rifa.image}" alt="${rifa.name}" class="rifa-image">
                <div class="rifa-content">
                    <h3 class="rifa-name">${rifa.name}</h3>
                    <div class="rifa-date">
                        <svg class="date-icon" viewBox="0 0 24 24">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                        </svg>
                        ${rifa.date}
                    </div>
                    <p class="rifa-price">${rifa.price}</p>
                </div>
                <div class="rifa-actions">
                    <button class="buy-button" onclick="buyTicket(${rifa.id}, '${rifa.name}')">
                        Comprar Boleto
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Initialize ticket selection functionality
     */
    initTicketSelection() {
        // Generate some random taken tickets for demo
        this.generateTakenTickets();
        this.generateTicketsGrid();
        this.updateSummary();
    }

    /**
     * Generate random taken tickets for demonstration
     */
    generateTakenTickets() {
        for (let i = 0; i < 150; i++) {
            const randomTicket = Math.floor(Math.random() * 1000) + 1;
            if (!this.takenTickets.includes(randomTicket)) {
                this.takenTickets.push(randomTicket);
            }
        }
    }

    /**
     * Generate the tickets grid
     */
    generateTicketsGrid() {
        const grid = document.getElementById('ticketsGrid');
        if (!grid) return;

        let ticketsHTML = '';

        for (let i = 1; i <= 1000; i++) {
            let ticketClass = 'available';
            if (this.takenTickets.includes(i)) {
                ticketClass = 'taken';
            } else if (this.selectedTickets.includes(i)) {
                ticketClass = 'selected';
            }

            ticketsHTML += `
                <div class="ticket-item ${ticketClass}" 
                     data-ticket="${i}" 
                     onclick="toggleTicket(${i})">
                    ${i.toString().padStart(4, '0')}
                </div>
            `;
        }

        grid.innerHTML = ticketsHTML;
    }

    /**
     * Toggle ticket selection
     */
    toggleTicket(ticketNumber) {
        // Don't allow selecting taken tickets
        if (this.takenTickets.includes(ticketNumber)) {
            return;
        }

        const ticketElement = document.querySelector(`[data-ticket="${ticketNumber}"]`);
        if (!ticketElement) return;
        
        if (this.selectedTickets.includes(ticketNumber)) {
            // Remove from selection
            this.selectedTickets = this.selectedTickets.filter(t => t !== ticketNumber);
            ticketElement.classList.remove('selected');
            ticketElement.classList.add('available');
        } else {
            // Add to selection
            this.selectedTickets.push(ticketNumber);
            ticketElement.classList.remove('available');
            ticketElement.classList.add('selected');
        }

        this.updateSummary();
        this.updateSelectedTicketsList();
    }

    /**
     * Update payment summary
     */
    updateSummary() {
        const totalTickets = this.selectedTickets.length;
        const totalAmount = totalTickets * this.ticketPrice;

        const totalTicketsElement = document.getElementById('totalTickets');
        const totalAmountElement = document.getElementById('totalAmount');
        
        if (totalTicketsElement) {
            totalTicketsElement.textContent = totalTickets;
        }
        if (totalAmountElement) {
            totalAmountElement.textContent = `$${totalAmount.toFixed(2)}`;
        }

        // Update checkout button state
        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.disabled = totalTickets === 0;
        }
    }

    /**
     * Update selected tickets list display
     */
    updateSelectedTicketsList() {
        const listContainer = document.getElementById('selectedTicketsList');
        if (!listContainer) return;
        
        if (this.selectedTickets.length === 0) {
            listContainer.innerHTML = '<p class="no-tickets">No has seleccionado ningún boleto</p>';
            return;
        }

        const sortedTickets = [...this.selectedTickets].sort((a, b) => a - b);
        let listHTML = '';

        sortedTickets.forEach(ticket => {
            listHTML += `
                <div class="selected-ticket-item">
                    <span>Boleto #${ticket.toString().padStart(4, '0')}</span>
                    <span class="remove-ticket-btn" onclick="removeTicket(${ticket})">×</span>
                </div>
            `;
        });

        listContainer.innerHTML = listHTML;
    }

    /**
     * Remove ticket from selection
     */
    removeTicket(ticketNumber) {
        const ticketElement = document.querySelector(`[data-ticket="${ticketNumber}"]`);
        if (!ticketElement) return;
        
        this.selectedTickets = this.selectedTickets.filter(t => t !== ticketNumber);
        ticketElement.classList.remove('selected');
        ticketElement.classList.add('available');

        this.updateSummary();
        this.updateSelectedTicketsList();
    }

    /**
     * Add random tickets to selection
     */
    addRandomTickets(quantity) {
        if (quantity === 0) {
            alert('Por favor, selecciona una cantidad mayor a 0');
            return;
        }

        // Get available tickets (not taken and not selected)
        const availableTickets = [];
        for (let i = 1; i <= 1000; i++) {
            if (!this.takenTickets.includes(i) && !this.selectedTickets.includes(i)) {
                availableTickets.push(i);
            }
        }

        if (availableTickets.length < quantity) {
            alert(`Solo hay ${availableTickets.length} boletos disponibles`);
            return;
        }

        // Select random tickets
        const newTickets = [];
        for (let i = 0; i < quantity; i++) {
            const randomIndex = Math.floor(Math.random() * availableTickets.length);
            const selectedTicket = availableTickets[randomIndex];
            
            newTickets.push(selectedTicket);
            this.selectedTickets.push(selectedTicket);
            
            // Remove from available tickets to avoid duplicates
            availableTickets.splice(randomIndex, 1);
            
            // Update ticket visual
            const ticketElement = document.querySelector(`[data-ticket="${selectedTicket}"]`);
            if (ticketElement) {
                ticketElement.classList.remove('available');
                ticketElement.classList.add('selected');
            }
        }

        this.updateSummary();
        this.updateSelectedTicketsList();

        alert(`Se agregaron ${quantity} boletos aleatorios: ${newTickets.sort((a, b) => a - b).map(t => t.toString().padStart(4, '0')).join(', ')}`);
    }

    /**
     * Clear all selected tickets
     */
    clearAllTickets() {
        if (this.selectedTickets.length === 0) {
            alert('No hay boletos seleccionados para limpiar');
            return;
        }

        if (confirm('¿Estás seguro de que quieres limpiar toda tu selección?')) {
            // Reset all selected tickets to available
            this.selectedTickets.forEach(ticket => {
                const ticketElement = document.querySelector(`[data-ticket="${ticket}"]`);
                if (ticketElement) {
                    ticketElement.classList.remove('selected');
                    ticketElement.classList.add('available');
                }
            });

            this.selectedTickets = [];
            
            const manualQuantityInput = document.getElementById('manualQuantity');
            if (manualQuantityInput) {
                manualQuantityInput.value = 0;
            }
            
            this.updateSummary();
            this.updateSelectedTicketsList();
        }
    }

    /**
     * Proceed to payment
     */
    proceedToPayment() {
        if (this.selectedTickets.length === 0) {
            alert('No has seleccionado ningún boleto');
            return;
        }

        const totalAmount = this.selectedTickets.length * this.ticketPrice;
        const ticketList = this.selectedTickets.sort((a, b) => a - b).map(t => t.toString().padStart(4, '0')).join(', ');
        
        if (confirm(`¿Proceder al pago?\n\nBoletos seleccionados: ${this.selectedTickets.length}\nNúmeros: ${ticketList}\nTotal: $${totalAmount.toFixed(2)}`)) {
            alert('Redirigiendo al procesador de pagos...\n\n¡Gracias por tu compra!');
            // Here you would redirect to payment processor
            // window.location.href = '/payment';
        }
    }

    /**
     * Submit contact form
     */
    submitContactForm(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = {
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            subject: formData.get('subject'),
            message: formData.get('message')
        };

        // Simulate form submission
        const submitBtn = event.target.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<div style="display: flex; align-items: center; gap: 0.5rem;"><div style="width: 20px; height: 20px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>Enviando...</div>';
        submitBtn.disabled = true;

        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            alert(`¡Gracias ${data.firstName}!\n\nTu mensaje ha sido enviado correctamente. Nos pondremos en contacto contigo pronto.`);
            event.target.reset();
        }, 2000);
    }
}

// Global functions for backward compatibility and HTML onclick handlers
let indexController;

function handleHeroClick() {
    const ticketSection = document.querySelector('.ticket-selection-container');
    if (ticketSection) {
        ticketSection.scrollIntoView({ 
            behavior: 'smooth' 
        });
    }
}

function buyTicket(id, name) {
    alert(`¡Compraste un boleto para la rifa: ${name}!\nID de rifa: ${id}`);
}

function buyPackage(tickets, price) {
    alert(`¡Compraste el paquete de ${tickets.toLocaleString()} boletos por $${price}!\n\nTus boletos estarán disponibles en tu cuenta en unos minutos.`);
}

function toggleTicket(ticketNumber) {
    if (indexController) {
        indexController.toggleTicket(ticketNumber);
    }
}

function removeTicket(ticketNumber) {
    if (indexController) {
        indexController.removeTicket(ticketNumber);
    }
}

function increaseQuantity() {
    const input = document.getElementById('manualQuantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value) || 0;
    const maxValue = parseInt(input.max) || 100;
    
    if (currentValue < maxValue) {
        input.value = currentValue + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('manualQuantity');
    if (!input) return;
    
    const currentValue = parseInt(input.value) || 0;
    const minValue = parseInt(input.min) || 0;
    
    if (currentValue > minValue) {
        input.value = currentValue - 1;
    }
}

function updateManualQuantity() {
    const input = document.getElementById('manualQuantity');
    if (!input) return;
    
    const value = parseInt(input.value) || 0;
    const minValue = parseInt(input.min) || 0;
    const maxValue = parseInt(input.max) || 100;
    
    if (value < minValue) {
        input.value = minValue;
    } else if (value > maxValue) {
        input.value = maxValue;
    }
}

function addRandomTickets() {
    const quantity = parseInt(document.getElementById('manualQuantity')?.value) || 0;
    
    if (indexController) {
        indexController.addRandomTickets(quantity);
        // Reset manual quantity
        const input = document.getElementById('manualQuantity');
        if (input) input.value = 0;
    }
}

function clearSelection() {
    if (indexController) {
        indexController.clearAllTickets();
    }
}

function proceedToPayment() {
    if (indexController) {
        indexController.proceedToPayment();
    }
}

function submitContactForm(event) {
    if (indexController) {
        indexController.submitContactForm(event);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    indexController = new IndexController();
});

// Export for module systems if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { IndexController };
}