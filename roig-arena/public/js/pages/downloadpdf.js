(() => {
            const { jsPDF } = window.jspdf || {};

            if (!jsPDF) {
                return;
            }

            const toDataUrl = async (url) => {
                const response = await fetch(url, { mode: 'cors' });

                if (!response.ok) {
                    throw new Error('No se pudo cargar la imagen QR');
                }

                const blob = await response.blob();

                return await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = () => reject(new Error('No se pudo leer la imagen QR'));
                    reader.readAsDataURL(blob);
                });
            };

            const sanitizeName = (value) => {
                return value
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '') || 'entrada';
            };

            document.querySelectorAll('[data-ticket-download]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const evento = button.dataset.evento || 'Evento';
                    const fecha = button.dataset.fecha || 'Por confirmar';
                    const hora = button.dataset.hora || 'Por confirmar';
                    const asiento = button.dataset.asiento || 'Asiento no disponible';
                    const entrada = button.dataset.entrada || '-';
                    const precio = button.dataset.precio || '-';
                    const codigo = button.dataset.codigo || '-';

                    const pdf = new jsPDF({ unit: 'mm', format: 'a4' });
                    let y = 20;

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(18);
                    pdf.text('Roig Arena - Entrada', 15, y);

                    y += 12;
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(12);
                    pdf.text(`Evento: ${evento}`, 15, y);
                    y += 8;
                    pdf.text(`Fecha: ${fecha}`, 15, y);
                    y += 8;
                    pdf.text(`Hora: ${hora}`, 15, y);
                    y += 8;
                    pdf.text(`Asiento: ${asiento}`, 15, y);
                    y += 8;
                    pdf.text(`Entrada #: ${entrada}`, 15, y);
                    y += 8;
                    pdf.text(`Precio: ${precio}`, 15, y);
                    y += 12;

                    pdf.setFont('helvetica', 'bold');
                    pdf.text('Codigo QR', 15, y);

                    try {
                        const qrUrl = `https://quickchart.io/qr?text=${encodeURIComponent(codigo)}&size=256&margin=1&ecLevel=M&format=png`;
                        const qrDataUrl = await toDataUrl(qrUrl);

                        y += 4;
                        pdf.addImage(qrDataUrl, 'PNG', 15, y, 45, 45);
                    } catch (error) {
                        y += 8;
                        pdf.setFont('helvetica', 'normal');
                        pdf.text('No se pudo generar el QR para esta entrada.', 15, y);
                    }

                    const safeEvento = sanitizeName(evento);
                    pdf.save(`entrada-${safeEvento}-${entrada}.pdf`);
                });
            });
        })();
