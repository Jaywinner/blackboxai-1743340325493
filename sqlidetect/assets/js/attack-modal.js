class AttackModal {
    constructor() {
        this.modal = document.createElement('div');
        this.modal.className = 'attack-modal';
        this.modal.innerHTML = `
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Attack Details</h2>
                <div class="modal-body">
                    <div class="detail-row">
                        <span class="detail-label">IP Address:</span>
                        <span class="detail-value" id="modal-ip"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Attack Type:</span>
                        <span class="detail-value" id="modal-type"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Timestamp:</span>
                        <span class="detail-value" id="modal-time"></span>
                    </div>
                    <div class="detail-row full-width">
                        <span class="detail-label">Malicious Query:</span>
                        <pre id="modal-query"></pre>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(this.modal);

        // Event listeners
        this.modal.querySelector('.close-modal').addEventListener('click', () => this.close());
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });
    }

    show(attackData) {
        document.getElementById('modal-ip').textContent = attackData.ip;
        document.getElementById('modal-type').textContent = attackData.injection_name;
        document.getElementById('modal-time').textContent = attackData.last_attack_time;
        document.getElementById('modal-query').textContent = attackData.attack_query;
        this.modal.style.display = 'block';
    }

    close() {
        this.modal.style.display = 'none';
    }
}

// Initialize modal when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const modal = new AttackModal();
    
    // Add click handlers to view buttons
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const attackId = this.dataset.attackId;
            fetch(`/api/attack-details.php?id=${attackId}`)
                .then(response => response.json())
                .then(data => modal.show(data))
                .catch(error => console.error('Error:', error));
        });
    });
});