/**
 * Componente General Summary Chart - ApexCharts Combo Chart.
 * 
 * Renderiza de forma desacoplada el gráfico mixto (Visitas Diarias [Barras] vs Clics Diarios [Línea])
 * para medir la conversión diaria dentro del modal de desglose de estadísticas.
 */
export function generalSummaryChart() {
  function renderChart() {
    if (typeof ApexCharts === 'undefined') {
      setTimeout(renderChart, 100);
      return;
    }

    // Buscar el contenedor objetivo ÚNICAMENTE dentro del modal activo (.modal-overlay)
    const container = document.querySelector('.modal-overlay .chart-summary-combo');
    if (!container) return;

    if (container.dataset.rendered === 'true') return;

    let dates  = [];
    let views  = [];
    let clicks = [];

    try {
      if (container.dataset.chartDates) {
        dates = JSON.parse(container.dataset.chartDates);
      }
      if (container.dataset.chartViews) {
        views = JSON.parse(container.dataset.chartViews);
      }
      if (container.dataset.chartClicks) {
        clicks = JSON.parse(container.dataset.chartClicks);
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
          name: 'Clics Diarios',
          type: 'line',
          data: clicks
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
          formatter: (y, { seriesIndex }) => {
            if (typeof y !== 'undefined') {
              const label = seriesIndex === 0 ? 'visitas' : 'clics';
              return `${y.toFixed(0)} ${label}`;
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

  // Intentar renderizar si el modal ya está desplegado
  renderChart();
}
