/*
 * Módulo: Dashboard y Análisis Financiero (GELIA Contabilidad)
 * Descripción: Maneja la generación de gráficas (Chart.js), actualización asíncrona de KPIs y exportación a PDF.
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.ContabilidadConfig || {};
    
    // Variables globales del módulo para destruir y regenerar gráficas
    let chartPlataformasInst = null;
    let chartVentasUtilidadInst = null;

    // ==========================================
    // 1. GRÁFICA PRINCIPAL (VISTA GENERAL INDEX)
    // ==========================================
    function renderGraficaUtilidad() {
        const ctx = document.getElementById('utilidadChart');
        if (!ctx) return;
        
        const dataRaw = config.graficaData || {};
        const labels = Object.keys(dataRaw).reverse();
        const values = labels.map(k => dataRaw[k].utilidad);

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Utilidad Neta',
                    data: values,
                    backgroundColor: values.map(v => v >= 0 ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)'),
                    borderColor: values.map(v => v >= 0 ? 'rgba(16, 185, 129, 1)' : 'rgba(239, 68, 68, 1)'),
                    borderWidth: 1, 
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX', { minimumFractionDigits: 2 })}` } }
                },
                scales: {
                    y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8', callback: (val) => `$${val.toLocaleString()}` } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }
    
    // Inicializar la gráfica al cargar la página
    renderGraficaUtilidad();

    // ==========================================
    // 2. FILTROS Y UI DEL DASHBOARD AVANZADO (MODAL)
    // ==========================================
    const dashFiltroTipo = document.getElementById('dash_filtro_tipo');
    if (dashFiltroTipo) {
        dashFiltroTipo.addEventListener('change', function () {
            document.getElementById('filtro_mes_container').classList.add('hidden');
            document.getElementById('filtro_dia_container').classList.add('hidden');
            document.getElementById('filtro_custom_container').classList.add('hidden');

            if (this.value === 'mes') document.getElementById('filtro_mes_container').classList.remove('hidden');
            if (this.value === 'dia') document.getElementById('filtro_dia_container').classList.remove('hidden');
            if (this.value === 'custom') document.getElementById('filtro_custom_container').classList.remove('hidden', 'flex');
        });
    }

    // ==========================================
    // 3. ACTUALIZACIÓN DE KPIs Y GRÁFICAS POR API
    // ==========================================
    const btnActualizarDash = document.getElementById('btnActualizarDashboard');
    if (btnActualizarDash) {
        btnActualizarDash.addEventListener('click', async function () {
            const filtro = dashFiltroTipo.value;
            let queryUrl = `${config.rutas.dashboardData}?filtro=${filtro}`;

            if (filtro === 'mes') {
                queryUrl += `&mes=${document.getElementById('dash_mes').value}&anio=${document.getElementById('dash_anio').value}`;
            } else if (filtro === 'dia') {
                queryUrl += `&fecha=${document.getElementById('dash_fecha').value}`;
            } else if (filtro === 'anio') {
                queryUrl += `&anio=${document.getElementById('dash_anio').value}`;
            } else if (filtro === 'custom') {
                queryUrl += `&inicio=${document.getElementById('dash_inicio').value}&fin=${document.getElementById('dash_fin').value}`;
            }

            const btnOriginalText = this.innerText;
            this.innerText = 'Generando...';
            this.disabled = true;

            try {
                const response = await fetch(queryUrl);
                const data = await response.json();

                const formatMoney = (val) => `$${parseFloat(val).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`;

                // Actualizar contadores web
                document.getElementById('kpi_ventas').innerText = formatMoney(data.kpis.ventas);
                document.getElementById('kpi_notas_ae').innerText = formatMoney(data.kpis.notasAE);
                document.getElementById('kpi_ganancias').innerText = formatMoney(data.kpis.ganancias);
                document.getElementById('kpi_margen').innerText = parseFloat(data.kpis.margen).toFixed(2) + '%';
                document.getElementById('kpi_perdidas').innerText = formatMoney(data.kpis.perdidas);
                document.getElementById('kpi_comisiones').innerText = formatMoney(data.kpis.comisiones);
                document.getElementById('kpi_envios_empresa').innerText = formatMoney(data.kpis.enviosEmpresa);
                document.getElementById('kpi_envios_clientes').innerText = `${data.kpis.enviosClientesCount} ped. / ${formatMoney(data.kpis.enviosClientesMonto)}`;

                // Renderizar Doughnut (Plataformas)
                const ctxPlat = document.getElementById('chartPlataformas');
                if (ctxPlat) {
                    if (chartPlataformasInst) chartPlataformasInst.destroy();
                    const platLabels = Object.keys(data.plataformas);
                    const platValues = Object.values(data.plataformas);

                    const coloresMarca = {
                        'paypal': '#3b82f6',
                        'stripe': '#8b5cf6',
                        'mercado pago': '#eab308',
                        'kueskipay': '#22c55e',
                        'open pay': '#14b8a6',
                        'openpay': '#14b8a6'
                    };

                    const bgColorsPlat = platLabels.map(label => coloresMarca[label.toLowerCase()] || '#94a3b8');

                    chartPlataformasInst = new Chart(ctxPlat.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: platLabels,
                            datasets: [{
                                data: platValues,
                                backgroundColor: bgColorsPlat,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 10 } } },
                                tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX')}` } }
                            }
                        }
                    });
                }

                // Renderizar Barras (Ventas vs Utilidad)
                const ctxVentasUt = document.getElementById('chartVentasUtilidad');
                if (ctxVentasUt) {
                    if (chartVentasUtilidadInst) chartVentasUtilidadInst.destroy();
                    const chartData = data.grafica || {};
                    const dates = Object.keys(chartData);
                    const dataVentas = dates.map(d => chartData[d].venta);
                    const dataUtilidad = dates.map(d => chartData[d].utilidad);

                    chartVentasUtilidadInst = new Chart(ctxVentasUt.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: dates,
                            datasets: [
                                { label: 'Venta Bruta', data: dataVentas, backgroundColor: 'rgba(59, 130, 246, 0.7)' },
                                { label: 'Utilidad Neta', data: dataUtilidad, backgroundColor: 'rgba(16, 185, 129, 0.8)' }
                            ]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top', labels: { color: '#94a3b8' } },
                                tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX', { minimumFractionDigits: 2 })}` } }
                            },
                            scales: {
                                y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8', callback: (val) => `$${val.toLocaleString()}` } },
                                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                            }
                        }
                    });
                }

            } catch (error) {
                console.error("Error cargando dashboard:", error);
                alert("Ocurrió un problema al obtener los datos del reporte.");
            } finally {
                this.innerText = btnOriginalText;
                this.disabled = false;
            }
        });
    }

    // ==========================================
    // 4. EXPORTACIÓN A PDF (CON CONVERSIÓN BASE64)
    // ==========================================
    const btnExportarPDF = document.getElementById('btnExportarPDF');
    if (btnExportarPDF) {
        btnExportarPDF.addEventListener('click', async function () {
            if (!chartPlataformasInst || !chartVentasUtilidadInst) {
                alert('Primero debes generar el reporte para visualizar los datos.');
                return;
            }

            const btnOriginalText = this.innerHTML;
            this.innerHTML = '<span class="material-symbols-outlined text-sm mr-2">hourglass_empty</span> Generando...';
            this.disabled = true;

            try {
                const imgPlataformas = chartPlataformasInst.toBase64Image();
                const imgVentas = chartVentasUtilidadInst.toBase64Image();

                const filtro = document.getElementById('dash_filtro_tipo').value;
                let periodoTexto = '';
                
                if (filtro === 'mes') {
                    const mesSelect = document.getElementById('dash_mes');
                    periodoTexto = `${mesSelect.options[mesSelect.selectedIndex].text} - ${document.getElementById('dash_anio').value}`;
                } else if (filtro === 'dia') {
                    periodoTexto = document.getElementById('dash_fecha').value;
                } else if (filtro === 'anio') {
                    periodoTexto = `Año ${document.getElementById('dash_anio').value}`;
                } else {
                    periodoTexto = `${document.getElementById('dash_inicio').value} a ${document.getElementById('dash_fin').value}`;
                }

                const response = await fetch('/contabilidad/generar-pdf', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ContabilidadConfig.token },
                    body: JSON.stringify({
                        img_plataformas: imgPlataformas,
                        img_ventas: imgVentas,
                        periodo: periodoTexto,
                        filtro: filtro,
                        mes: document.getElementById('dash_mes').value,
                        anio: document.getElementById('dash_anio').value,
                        fecha: document.getElementById('dash_fecha').value,
                        inicio: document.getElementById('dash_inicio').value,
                        fin: document.getElementById('dash_fin').value
                    })
                });

                if (!response.ok) throw new Error('Error al generar PDF');

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Reporte_Financiero_Bellaroma_${new Date().getTime()}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();

            } catch (error) {
                console.error(error);
                alert("Hubo un error al generar el documento PDF.");
            } finally {
                this.innerHTML = btnOriginalText;
                this.disabled = false;
            }
        });
    }
});