// Chatbot sederhana untuk Seikat Bungo
(function () {
    // Konfigurasi
    var whatsappNumber = '6283865425936';
    var options = [
        { label: 'Fresh Flowers', value: 'Fresh Flowers' },
        { label: 'Bouquet', value: 'Bouquet' },
        { label: 'Artificial', value: 'Artificial' },
        { label: 'Custom Bouquet', value: 'Custom Bouquet' }
    ];
    // Buat elemen chatbot
    var style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = '/css/chatbot.css';
    document.head.appendChild(style);

    var chatBtn = document.createElement('div');
    chatBtn.id = 'sb-chatbot-btn';
    chatBtn.innerText = '💬';
    document.body.appendChild(chatBtn);

    var chatPopup = document.createElement('div');
    chatPopup.id = 'sb-chatbot-popup';
    chatPopup.innerHTML = `
    <div class="sb-chatbot-header">🌸 Hai, Sahabat Fellie!</div>
    <div class="sb-chatbot-body">Mau pesan bunga apa hari ini?</div>
    <div class="sb-chatbot-options">
      ${options.map(opt => `<button data-value="${opt.value}">${opt.label}</button>`).join('')}
    </div>
  `;
    document.body.appendChild(chatPopup);

    // Animasi fade-in
    setTimeout(function () {
        chatPopup.classList.add('show');
    }, 300);

    // Toggle popup
    chatBtn.onclick = function () {
        chatPopup.classList.toggle('show');
    };

    // Klik di luar popup menutup
    document.addEventListener('click', function (e) {
        if (!chatPopup.contains(e.target) && e.target !== chatBtn) {
            chatPopup.classList.remove('show');
        }
    });

    // Pilihan tombol
    chatPopup.querySelectorAll('button').forEach(function (btn) {
        btn.onclick = function (e) {
            e.stopPropagation();
            var pesan = encodeURIComponent('Halo Fellie Florist, saya ingin pesan ' + btn.dataset.value + '.');
            window.open('https://wa.me/' + whatsappNumber + '?text=' + pesan, '_blank');
            chatPopup.classList.remove('show');
        };
    });
})();
