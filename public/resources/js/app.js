document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Consumimos el endpoint local
        const response = await fetch('./../../api.php');
        const data = await response.json();

        const tbody = document.querySelector('#tableBody');
        const tableData = [...data].reverse(); // Tabla: Hoy primero

        // 1. Llenar la Tabla
        tableData.forEach(log => {
            const isFailed = log.notification_status === 'failed';
            const temp = isFailed ? 'N/A' : `${log.temperature_celsius}°C`;
            const liters = isFailed ? 'N/A' : `${log.total_liters}L`;
            const boils = isFailed ? 'N/A' : log.boils_needed;
            
            const statusClass = isFailed ? 'badge-fail' : 'badge-ok';
            const statusText = isFailed ? 'Fallo' : 'OK';

            tbody.innerHTML += `
                <tr>
                    <td style="font-family: monospace; color: var(--accent-blue);">${log.run_date}</td>
                    <td>${temp}</td>
                    <td style="font-weight: bold; color: white;">${liters}</td>
                    <td>${boils}</td>
                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                </tr>
            `;
        });

        // 2. Pintar el Gráfico
        const validData = data.filter(log => log.notification_status !== 'failed');
        const labels = validData.map(log => log.run_date.substring(5)); // Mostrar MM-DD
        const temps = validData.map(log => log.temperature_celsius);
        const liters = validData.map(log => log.total_liters);

        const ctx = document.getElementById('hydrationChart').getContext('2d');
        
        // Chart está disponible globalmente gracias a chart.umd.js
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Temperatura (°C)',
                        data: temps,
                        borderColor: 'rgba(239, 68, 68, 0.8)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        yAxisID: 'y',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Litros Requeridos',
                        data: liters,
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y1',
                        type: 'bar',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', grid: { color: '#374151' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } },
                    x: { grid: { color: '#374151' } }
                },
                plugins: { legend: { labels: { color: '#D1D5DB' } } }
            }
        });

    } catch (error) {
        console.error("Fallo de conexión con la API:", error);
    }
});