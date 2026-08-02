/**
 * Componente General Summary Chart - ApexCharts Combo Chart.
 * 
 * Renderiza de forma desacoplada el gráfico mixto (Visitas Diarias [Barras] vs Clics Diarios [Línea])
 * utilizando la paleta de colores global definida en App/Public/Css/chart-theme.css.
 */
export function generalSummaryChart() {
  /**
   * Obtiene los colores configurados dinámicamente en las variables CSS del archivo chart-theme.css
   */
  function getChartColors() {
    const style = getComputedStyle(document.documentElement);
    const getVar = (name, fallback) => style.getPropertyValue(name).trim() || fallback;

    return {
      barPrimary:   getVar('--chart-bar-primary', '#dc2626'),
      linePrimary:  getVar('--chart-line-primary', '#2563eb'),
      gridBorder:   getVar('--chart-grid-border', '#e2e8f0'),
      axisText:     getVar('--chart-axis-text', '#64748b'),
      tooltipTheme: getVar('--chart-tooltip-theme', 'dark')
    };
  }

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
      if (container.dataset.chartDates)  dates  = JSON.parse(container.dataset.chartDates);
      if (container.dataset.chartViews)  views  = JSON.parse(container.dataset.chartViews);
      if (container.dataset.chartClicks) clicks = JSON.parse(container.dataset.chartClicks);
    } catch (e) {
      console.error('Error al parsear datos en generalSummaryChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();

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
      colors: [themeColors.barPrimary, themeColors.linePrimary],
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
          style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 },
          formatter: (val) => Math.round(val)
        }
      },
      grid: {
        borderColor: themeColors.gridBorder,
        strokeDashArray: 4
      },
      tooltip: {
        shared: true,
        intersect: false,
        theme: themeColors.tooltipTheme,
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
