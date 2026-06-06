// js/booking.js
// Handles AJAX time slot checking and pricing validation

document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('booking_date');
    const futsalIdInput = document.getElementById('futsal_id');
    const slotContainer = document.getElementById('slot-container');
    const bookingSummary = document.getElementById('booking-summary');
    const submitBtn = document.getElementById('submit-booking-btn');
    
    // Check if we are currently on the book.php page
    if (dateInput && futsalIdInput) {
        
        // Initial slot load when page opens
        loadAvailableSlots();
        
        // Fetch new slots when date changes
        dateInput.addEventListener('change', loadAvailableSlots);
        
        function loadAvailableSlots() {
            const dateVal = dateInput.value;
            const futsalId = futsalIdInput.value;
            
            if (!dateVal || !futsalId) return;
            
            // Show a simple loading state in summary
            bookingSummary.innerHTML = "<p class='text-muted'>Checking slot availability...</p>";
            submitBtn.disabled = true;
            
            // Clear any previously checked slots
            const radios = document.querySelectorAll('input[name="start_time"]');
            radios.forEach(radio => {
                radio.checked = false;
                const parent = radio.closest('.slot-option');
                parent.classList.remove('booked', 'pending');
                radio.disabled = false;
                
                // Reset status label text inside the card
                const label = parent.querySelector('.slot-status-label');
                if (label) label.textContent = 'Available';
            });
            
            // Fetch booked slots via AJAX endpoint
            fetch(`check_slots.php?futsal_id=${futsalId}&date=${dateVal}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const booked = data.booked_slots;
                        
                        radios.forEach(radio => {
                            const timeVal = radio.value; // e.g. '07:00'
                            const parent = radio.closest('.slot-option');
                            const label = parent.querySelector('.slot-status-label');
                            
                            // Check if this time slot is in the returned booked slots array
                            if (booked.hasOwnProperty(timeVal)) {
                                const status = booked[timeVal];
                                radio.disabled = true;
                                
                                if (status === 'approved') {
                                    parent.classList.add('booked');
                                    if (label) label.textContent = 'Booked';
                                } else if (status === 'pending') {
                                    parent.classList.add('pending');
                                    if (label) label.textContent = 'Pending';
                                }
                            }
                        });
                        
                        bookingSummary.innerHTML = "<p style='color: var(--text-muted); font-size: 0.9rem;'>Please select a date and an available time slot above.</p>";
                    } else {
                        bookingSummary.innerHTML = `<p class='text-danger'>Error checking slots: ${data.error}</p>`;
                    }
                })
                .catch(error => {
                    bookingSummary.innerHTML = `<p class='text-danger'>Failed to load availability. Please refresh the page.</p>`;
                    console.error('Error fetching slots:', error);
                });
        }
        
        // Add dynamic feedback when choosing a slot
        if (slotContainer) {
            slotContainer.addEventListener('change', function (e) {
                if (e.target && e.target.name === 'start_time') {
                    const selectedTime = e.target.value;
                    const dateVal = dateInput.value;
                    const price = document.getElementById('price_per_hour').value;
                    
                    // Format time for readability (e.g. '07:00' -> '07:00 AM')
                    const [hours, minutes] = selectedTime.split(':');
                    const hourInt = parseInt(hours);
                    const ampm = hourInt >= 12 ? 'PM' : 'AM';
                    const displayHour = hourInt % 12 === 0 ? 12 : hourInt % 12;
                    const endHour = (hourInt + 1) % 12 === 0 ? 12 : (hourInt + 1) % 12;
                    const endAmpm = (hourInt + 1) >= 12 ? 'PM' : 'AM';
                    
                    const timeFormatted = `${displayHour}:00 ${ampm} to ${(hourInt + 1) % 24}:00 ${endAmpm}`;
                    
                    // Display booking checkout overview
                    bookingSummary.innerHTML = `
                        <div style="background-color: var(--light-green); border: 1px solid var(--primary-color); border-radius: 8px; padding: 15px; text-align: left;">
                            <h4 style="color: var(--primary-darkest); margin-bottom: 5px; font-size: 0.95rem;">Booking Confirmation Summary</h4>
                            <p style="margin: 0; font-size: 0.88rem;"><b>Date:</b> ${dateVal}</p>
                            <p style="margin: 0; font-size: 0.88rem;"><b>Time Slot:</b> ${timeFormatted} (1 Hour)</p>
                            <p style="margin: 5px 0 0; font-size: 1.05rem; color: var(--primary-color); font-weight: 700;">Total Price: Rs. ${parseFloat(price).toLocaleString()}</p>
                        </div>
                    `;
                    
                    // Enable checkout submission
                    submitBtn.disabled = false;
                }
            });
        }
    }
});
