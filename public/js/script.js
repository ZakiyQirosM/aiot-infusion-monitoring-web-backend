// @ts-nocheck

// Fungsi untuk toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const toggleButton = document.querySelector('.toggle-btn-open');

    sidebar.classList.toggle('show');
    content.classList.toggle('shift');

    // Sembunyikan tombol ketika sidebar dibuka
    if (sidebar.classList.contains('show')) {
        toggleButton.classList.add('hide');
    } else {
        toggleButton.classList.remove('hide');
    }
}

function showAlert(message, type = 'success', duration = 3000) {
    const alertBox = document.createElement('div');
    alertBox.className = `custom-alert ${type}`;
    alertBox.innerHTML = `
        <i class="fa-solid fa-circle-${type === 'success' ? 'check' : 'exclamation'}"></i>
        <span>${message}</span>
    `;

    // Style dasar
    Object.assign(alertBox.style, {
        position: 'fixed',
        top: '20px',
        left: '50%',
        transform: 'translateX(-50%)',
        backgroundColor: type === 'success' ? '#4CAF50' : '#f44336',
        color: 'white',
        padding: '12px 20px',
        borderRadius: '8px',
        zIndex: '9999',
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        boxShadow: '0 2px 6px rgba(0,0,0,0.2)',
        opacity: '1',
        transition: 'opacity 0.5s ease',
    });

    document.body.appendChild(alertBox);

    setTimeout(() => {
        alertBox.style.opacity = '0';
    }, duration - 500);

    setTimeout(() => {
        alertBox.remove();
    }, duration);
}

document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('errorPopup');

    if (popup) {
    setTimeout(() => {
        popup.classList.add('fade-out');
    }, 2000);

    setTimeout(() => {
        popup.remove();
    }, 2500);
}

    // ✅ Event untuk tombol cari pasien
    const btnSearch = document.getElementById('btn-search');
    if (btnSearch) {
        btnSearch.addEventListener('click', async () => {
            const noRegister = document.getElementById('identifier')?.value.trim();

            if (noRegister) {
                try {
                    const response = await fetch(`/register/search?identifier=${noRegister}`);
                    if (!response.ok) throw new Error('Data tidak ditemukan.');

                    const data = await response.json();

                    document.getElementById('name').value = data.name;
                    document.getElementById('age').value = data.age;
                    document.getElementById('location').value = data.location;
                } catch (error) {
                    alert(error.message);
                }
            }
        });
    }

    // ✅ Event click untuk tombol pilih device
    document.addEventListener('click', (event) => {
        if (event.target.classList.contains('select-device')) {
            const deviceId = event.target.dataset.deviceId;
            console.log('Device clicked:', deviceId);
            selectDevice(deviceId);
        }
    });

    // ✅ Konfirmasi Popup Logic
    const yesBtn = document.getElementById('confirmYes');
    const noBtn = document.getElementById('confirmNo');

    if (yesBtn) {
        yesBtn.addEventListener('click', () => {
            if (currentFormId) {
                document.getElementById(currentFormId)?.submit();
            }
            closeConfirmPopup();
        });
    }

    if (noBtn) {
        noBtn.addEventListener('click', () => {
            closeConfirmPopup();
        });
    }
});


// 🔄 Fungsi pilih device
function selectDevice(deviceId) {
    // Show loading indicator
    const loadingIndicator = createAlertBox('Memproses...', '#2196F3', 'fa-solid fa-spinner fa-spin');
    document.body.appendChild(loadingIndicator);

    fetch('/devices/assign', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            id_perangkat_infusee: deviceId
        })
    })
    .then(response => {
        loadingIndicator.remove();
        
        if (!response.ok) {
            // Handle specific HTTP error codes
            if (response.status === 400) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Permintaan tidak valid');
                });
            }
            if (response.status === 404) {
                throw new Error('Endpoint tidak ditemukan');
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Success handling
            handleSuccessResponse(data);
        } else {
            // Handle API-level errors
            handleErrorResponse(data.error || 'Terjadi kesalahan yang tidak diketahui');
        }
    })
    .catch(error => {
        loadingIndicator.remove();
        console.error('Error:', error);
        
        // Handle different error types
        let errorMessage = error.message;
        
        if (error instanceof TypeError) {
            errorMessage = 'Koneksi jaringan bermasalah. Periksa koneksi internet Anda.';
        } else if (error.message.includes('Failed to fetch')) {
            errorMessage = 'Tidak dapat terhubung ke server. Silakan coba lagi nanti.';
        }
        
        handleErrorResponse(errorMessage);
    });
}

// Helper function to create alert boxes
function createAlertBox(message, color, iconClass = '') {
    const alertBox = document.createElement('div');
    alertBox.style.position = 'fixed';
    alertBox.style.top = '20px';
    alertBox.style.left = '50%';
    alertBox.style.transform = 'translateX(-50%)';
    alertBox.style.backgroundColor = color;
    alertBox.style.color = 'white';
    alertBox.style.padding = '12px 20px';
    alertBox.style.borderRadius = '8px';
    alertBox.style.zIndex = '9999';
    alertBox.style.display = 'flex';
    alertBox.style.alignItems = 'center';
    alertBox.style.gap = '10px';
    alertBox.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    alertBox.style.transition = 'all 0.3s ease';

    if (iconClass) {
        const icon = document.createElement('i');
        icon.className = iconClass;
        icon.style.fontSize = '20px';
        icon.style.color = 'white';
        alertBox.appendChild(icon);
    }

    const messageElement = document.createElement('span');
    messageElement.innerText = message;
    alertBox.appendChild(messageElement);

    return alertBox;
}

// Handle success responses
function handleSuccessResponse(data) {
    // Remove device from list if exists
    document.querySelector(`[data-id="${data.id_perangkat_infusee}"]`)?.remove();
    document.querySelector('.patient-info')?.remove();

    const successBox = createAlertBox(
        data.message || 'Perangkat berhasil ditetapkan!',
        '#4CAF50',
        'fa-solid fa-circle-check'
    );
    document.body.appendChild(successBox);

    setTimeout(() => {
        successBox.style.opacity = '0';
        successBox.style.top = '0';
        setTimeout(() => {
            successBox.remove();
            window.location.href = '/infusee';
        }, 300);
    }, 2000);
}

// Handle error responses
function handleErrorResponse(errorMessage) {
    // Custom messages for specific errors
    const errorMessages = {
        'Data infusion session tidak ditemukan atau sudah memiliki perangkat.': 
            'Tidak dapat menetapkan perangkat. Pastikan data pasien telah dimasukkan dengan benar dan sesi infus belum memiliki perangkat.',
        'Perangkat tidak tersedia atau sudah digunakan.':
            'Perangkat sedang digunakan atau tidak tersedia. Silakan pilih perangkat lain.',
        'Tidak ada sesi infus yang tersedia untuk dialokasikan.':
            'Tidak ada pasien yang membutuhkan infus saat ini. Silakan buat sesi infus terlebih dahulu.'
    };

    const displayMessage = errorMessages[errorMessage] || 
        `Tidak dapat menetapkan perangkat: ${errorMessage}`;

    const errorBox = createAlertBox(
        displayMessage,
        '#f44336',
        'fa-solid fa-circle-exclamation'
    );
    document.body.appendChild(errorBox);

    setTimeout(() => {
        errorBox.style.opacity = '0';
        errorBox.style.top = '0';
        setTimeout(() => errorBox.remove(), 300);
    }, 5000);
}
// 🔄 Variabel & fungsi konfirmasi popup
var currentFormId = null;

function openConfirmPopup(formId) {
    currentFormId = formId;
    document.getElementById('confirm-overlay')?.classList.add('active');
}

function closeConfirmPopup() {
    document.getElementById('confirm-overlay')?.classList.remove('active');
    currentFormId = null;
}
