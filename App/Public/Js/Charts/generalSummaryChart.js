/**
 * Componente General Summary Chart - ApexCharts Combo Chart.
 * 
 * Renderiza de forma desacoplada el gráfico mixto (Barras + Tendencia) para el resumen general
 * dentro del modal de desglose de visitas de la tarjeta de estadísticas.
 */
(function (global) {
  function generalSummaryChart() {
    function renderChart() {
      if (typeof ApexCharts === 'undefined') {
        setTimeout(renderChart, 100);
        return;
      }

      // Buscar el contenedor objetivo mediante su clase identificadora (.chart-summary-combo)
      const container = document.querySelector('.modal-overlay .chart-summary-combo') || document.querySelector('.chart-summary-combo');
      if (!container) return;

      if (container.dataset.rendered === 'true') return;

      let dates = [];
      let views = [];

      try {
        if (container.dataset.chartDates) {
          dates = JSON.parse(container.dataset.chartDates);
        }
        if (container.dataset.chartViews) {
          views = JSON.parse(container.dataset.chartViews);
        }
      } catch (e) {
        console.error('Error al parsear datos en generalSummaryChart:', e);
        return;
      }

      container.dataset.rendered = 'true';
      container.innerHTML = '';

      const options = {
        series: [
          {
            name: 'Visitas Diarias',
            type: 'column',
            data: views
          },
          {
            name: 'Tendencia',
            type: 'line',
            data: views
          }
        ],
        chart: {
          height: 260,
          type: 'line',
          toolbar: { show: false },
          animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800
          }
        },
        stroke: {
          width: [0, 3],
          curve: 'smooth'
        },
        plotOptions: {
          bar: {
            columnWidth: '45%',
            borderRadius: 5
          }
        },
        colors: ['#dc2626', '#2563eb'],
        labels: dates,
        markers: {
          size: 4,
          strokeWidth: 2,
          hover: { size: 6 }
        },
        xaxis: {
          type: 'category',
          labels: {
            show: false
          },
          axisBorder: {
            show: false
          },
          axisTicks: {
            show: false
          }
        },
        yaxis: {
          labels: {
            style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 },
            formatter: (val) => Math.round(val)
          }
        },
        grid: {
          borderColor: '#e2e8f0',
          strokeDashArray: 4
        },
        tooltip: {
          shared: true,
          intersect: false,
          theme: 'dark',
          y: {
            formatter: (y) => {
              if (typeof y !== 'undefined') {
                return `${y.toFixed(0)} visitas`;
              }
              return y;
            }
          }
        }
      };

      const chart = new ApexCharts(container, options);
      chart.render();
    }

    // Escuchar cuando el modal overlay se añade al DOM mediante MutationObserver
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1 && (node.classList.contains('modal-overlay') || node.querySelector('.chart-summary-combo'))) {
              setTimeout(renderChart, 120);
            }
          });
        }
      });
    });

    const startObserving = () => {
      if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
      }
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', startObserving);
    } else {
      startObserving();
    }

    // Gatillar renderizado al hacer clic en el botón del modal
    document.addEventListener('click', (e) => {
      if (e.target.closest('.modal-btn')) {
        setTimeout(renderChart, 150);
      }
    });

    // Intentar renderizar de inmediato si el contenedor ya existe
    renderChart();
  }

  global.generalSummaryChart = generalSummaryChart;
  generalSummaryChart();
})(typeof window !== 'undefined' ? window : this);
