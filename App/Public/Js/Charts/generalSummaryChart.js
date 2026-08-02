/**
 * Componente General Summary Chart - ApexCharts.
 * 
 * Gestiona desacopladamente:
 * 1. Gráfico Mixto / Combo (Visitas Diarias [Barras] vs Clics Diarios [Línea])
 * 2. Gráfico Horizontal Dynamic Loaded (Visitas por Semana)
 * 3. Gráfico de Barras de Visitas Únicas por Día (Solo Barras)
 * 4. Gráfico Horizontal Dynamic Loaded (Visitas Únicas por Semana)
 * 5. Gráfico de Barras de Clics por Día (Solo Barras)
 * 6. Gráfico Horizontal Dynamic Loaded (Top Enlaces más Clicados - Máximo Top 10)
 * 7. Gráfico Stacked Column por Red Social (Rendimiento por Día de la Semana y Franja Horaria)
 * 8. Gráfico Directo de Línea con Data Labels para Horarios Más Concurridos
 */
export function generalSummaryChart() {
  /**
   * Obtiene los colores configurados dinámicamente en las variables CSS de chart-theme.css
   */
  function getChartColors() {
    const style = getComputedStyle(document.documentElement);
    const getVar = (name, fallback) => style.getPropertyValue(name).trim() || fallback;

    return {
      barPrimary:   getVar('--chart-bar-primary', '#dc2626'),
      linePrimary:  getVar('--chart-line-primary', '#2563eb'),
      barSecondary: getVar('--chart-bar-secondary', '#059669'),
      gridBorder:   getVar('--chart-grid-border', '#e2e8f0'),
      axisText:     getVar('--chart-axis-text', '#64748b'),
      tooltipTheme: getVar('--chart-tooltip-theme', 'dark')
    };
  }

  // Paleta vibrante distribuida para los gráficos horizontales (Dynamic Loaded Chart)
  const distributedColors = [
    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
    '#8b5cf6', '#06b6d4', '#ec4899', '#f97316',
    '#14b8a6', '#6366f1'
  ];

  /**
   * 1. Renderiza el gráfico Mixto Combo (Visitas vs Clics)
   */
  function renderComboChart() {
    const container = document.querySelector('.modal-overlay .chart-summary-combo');
    if (!container || container.dataset.rendered === 'true') return;

    let dates  = [];
    let views  = [];
    let clicks = [];

    try {
      if (container.dataset.chartDates)  dates  = JSON.parse(container.dataset.chartDates);
      if (container.dataset.chartViews)  views  = JSON.parse(container.dataset.chartViews);
      if (container.dataset.chartClicks) clicks = JSON.parse(container.dataset.chartClicks);
    } catch (e) {
      console.error('Error al parsear datos en renderComboChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();

    const options = {
      series: [
        { name: 'Visitas Diarias', type: 'column', data: views },
        { name: 'Clics Diarios',   type: 'line',   data: clicks }
      ],
      chart: {
        height: 260,
        type: 'line',
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
      },
      stroke: { width: [0, 3], curve: 'smooth' },
      plotOptions: { bar: { columnWidth: '45%', borderRadius: 5 } },
      colors: [themeColors.barPrimary, themeColors.linePrimary],
      labels: dates,
      markers: { size: 4, strokeWidth: 2, hover: { size: 6 } },
      xaxis: { type: 'category', labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 }, formatter: (val) => Math.round(val) } },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
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

    new ApexCharts(container, options).render();
  }

  /**
   * 2. Renderiza el gráfico Horizontal Dynamic Loaded (Visitas por Semana)
   */
  function renderWeekChart() {
    const container = document.querySelector('.modal-overlay .chart-summary-weeks');
    if (!container || container.dataset.rendered === 'true') return;

    let weeks = [];
    let views = [];

    try {
      if (container.dataset.chartWeeks)     weeks = JSON.parse(container.dataset.chartWeeks);
      if (container.dataset.chartWeekViews) views = JSON.parse(container.dataset.chartWeekViews);
    } catch (e) {
      console.error('Error al parsear datos en renderWeekChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();
    const dynamicHeight = Math.max(180, weeks.length * 45);

    const options = {
      series: [{ name: 'Visitas', data: views }],
      chart: {
        type: 'bar',
        height: dynamicHeight,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: true,
          distributed: true,
          barHeight: '55%'
        }
      },
      colors: distributedColors,
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: { colors: ['#ffffff'], fontWeight: 600, fontSize: '11px' },
        formatter: (val) => `${val} visitas`,
        offsetX: 5
      },
      xaxis: {
        categories: weeks,
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: { colors: themeColors.axisText, fontSize: '12px', fontWeight: 600 }
        }
      },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
      legend: { show: false },
      tooltip: {
        theme: themeColors.tooltipTheme,
        y: { formatter: (val) => `${val} visitas totales` }
      }
    };

    new ApexCharts(container, options).render();
  }

  /**
   * 3. Renderiza el gráfico de Barras de Visitas Únicas por Día (Solo Barras)
   */
  function renderUniquesComboChart() {
    const container = document.querySelector('.modal-overlay .chart-summary-uniques-combo');
    if (!container || container.dataset.rendered === 'true') return;

    let dates   = [];
    let uniques = [];

    try {
      if (container.dataset.chartDates)   dates   = JSON.parse(container.dataset.chartDates);
      if (container.dataset.chartUniques) uniques = JSON.parse(container.dataset.chartUniques);
    } catch (e) {
      console.error('Error al parsear datos en renderUniquesComboChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();

    const options = {
      series: [
        { name: 'Visitas Únicas', data: uniques }
      ],
      chart: {
        height: 260,
        type: 'bar',
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
      },
      plotOptions: { bar: { columnWidth: '45%', borderRadius: 5 } },
      colors: [themeColors.barPrimary],
      labels: dates,
      xaxis: { type: 'category', labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 }, formatter: (val) => Math.round(val) } },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
      tooltip: {
        theme: themeColors.tooltipTheme,
        y: { formatter: (val) => `${val} visitas únicas` }
      }
    };

    new ApexCharts(container, options).render();
  }

  /**
   * 4. Renderiza el gráfico Horizontal Dynamic Loaded (Visitas Únicas por Semana)
   */
  function renderUniquesWeekChart() {
    const container = document.querySelector('.modal-overlay .chart-summary-uniques-weeks');
    if (!container || container.dataset.rendered === 'true') return;

    let weeks   = [];
    let uniques = [];

    try {
      if (container.dataset.chartWeeks)       weeks   = JSON.parse(container.dataset.chartWeeks);
      if (container.dataset.chartWeekUniques) uniques = JSON.parse(container.dataset.chartWeekUniques);
    } catch (e) {
      console.error('Error al parsear datos en renderUniquesWeekChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();
    const dynamicHeight = Math.max(180, weeks.length * 45);

    const options = {
      series: [{ name: 'Usuarios Únicos', data: uniques }],
      chart: {
        type: 'bar',
        height: dynamicHeight,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: true,
          distributed: true,
          barHeight: '55%'
        }
      },
      colors: distributedColors,
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: { colors: ['#ffffff'], fontWeight: 600, fontSize: '11px' },
        formatter: (val) => `${val} únicas`,
        offsetX: 5
      },
      xaxis: {
        categories: weeks,
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: { colors: themeColors.axisText, fontSize: '12px', fontWeight: 600 }
        }
      },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
      legend: { show: false },
      tooltip: {
        theme: themeColors.tooltipTheme,
        y: { formatter: (val) => `${val} usuarios únicos` }
      }
    };

    new ApexCharts(container, options).render();
  }

  /**
   * 5. Renderiza el gráfico de Clics por Día (Solo Barras)
   */
  function renderClicksDailyChart() {
    const container = document.querySelector('.modal-overlay .chart-summary-clicks-daily');
    if (!container || container.dataset.rendered === 'true') return;

    let dates  = [];
    let clicks = [];

    try {
      if (container.dataset.chartDates)  dates  = JSON.parse(container.dataset.chartDates);
      if (container.dataset.chartClicks) clicks = JSON.parse(container.dataset.chartClicks);
    } catch (e) {
      console.error('Error al parsear datos en renderClicksDailyChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();

    const options = {
      series: [
        { name: 'Clics Diarios', data: clicks }
      ],
      chart: {
        height: 260,
        type: 'bar',
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
      },
      plotOptions: { bar: { columnWidth: '45%', borderRadius: 5 } },
      colors: [themeColors.linePrimary],
      labels: dates,
      xaxis: { type: 'category', labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 }, formatter: (val) => Math.round(val) } },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
      tooltip: {
        theme: themeColors.tooltipTheme,
        y: { formatter: (val) => `${val} clics` }
      }
    };

    new ApexCharts(container, options).render();
  }

  /**
   * 6. Renderiza el gráfico Horizontal Dynamic Loaded (Top Enlaces más Clicados)
   */
  function renderTopLinksChart() {
    const container = document.querySelector('.modal-overlay .chart-summary-top-links');
    if (!container || container.dataset.rendered === 'true') return;

    let links  = [];
    let clicks = [];

    try {
      if (container.dataset.chartLinks)      links  = JSON.parse(container.dataset.chartLinks);
      if (container.dataset.chartLinkClicks) clicks = JSON.parse(container.dataset.chartLinkClicks);
    } catch (e) {
      console.error('Error al parsear datos en renderTopLinksChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();
    const dynamicHeight = Math.max(200, links.length * 45);

    const options = {
      series: [{ name: 'Clics', data: clicks }],
      chart: {
        type: 'bar',
        height: dynamicHeight,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          horizontal: true,
          distributed: true,
          barHeight: '55%'
        }
      },
      colors: distributedColors,
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: { colors: ['#ffffff'], fontWeight: 600, fontSize: '11px' },
        formatter: (val) => `${val} clics`,
        offsetX: 5
      },
      xaxis: {
        categories: links,
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          align: 'left',
          minWidth: 150,
          maxWidth: 180,
          style: { colors: themeColors.axisText, fontSize: '12px', fontWeight: 600 }
        }
      },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
      legend: { show: false },
      tooltip: {
        theme: themeColors.tooltipTheme,
        y: { formatter: (val) => `${val} clics registrados` }
      }
    };

    new ApexCharts(container, options).render();
  }

  /**
   * 7. Renderiza los gráficos Stacked Column por Red Social (Rendimiento por Día y Franja Horaria)
   */
  function renderSocialStackedCharts() {
    const containers = document.querySelectorAll('.modal-overlay .chart-social-stacked');
    if (!containers.length) return;

    containers.forEach((container) => {
      if (container.dataset.rendered === 'true') return;

      let categories = [];
      let series     = [];

      try {
        if (container.dataset.chartCategories) categories = JSON.parse(container.dataset.chartCategories);
        if (container.dataset.chartSeries)     series     = JSON.parse(container.dataset.chartSeries);
      } catch (e) {
        console.error('Error al parsear datos en renderSocialStackedCharts:', e);
        return;
      }

      container.dataset.rendered = 'true';
      container.innerHTML = '';

      const themeColors = getChartColors();
      const timeSlotColors = ['#059669', '#2563eb', '#8b5cf6', '#dc2626'];

      const options = {
        series: series,
        chart: {
          type: 'bar',
          height: 260,
          stacked: true,
          toolbar: { show: false },
          animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '45%',
            borderRadius: 4
          }
        },
        colors: timeSlotColors,
        xaxis: {
          categories: categories,
          labels: {
            style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 }
          },
          axisBorder: { show: false },
          axisTicks: { show: false }
        },
        yaxis: {
          labels: {
            style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 },
            formatter: (val) => Math.round(val)
          }
        },
        grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
        legend: {
          position: 'top',
          horizontalAlign: 'center',
          labels: { colors: themeColors.axisText },
          fontSize: '11px'
        },
        tooltip: {
          theme: themeColors.tooltipTheme,
          y: { formatter: (val) => `${val} visitas` }
        }
      };

      new ApexCharts(container, options).render();
    });
  }

  /**
   * 8. Renderiza el gráfico directo de Línea con Data Labels para Horarios Más Concurridos (Nodos de Alto Contraste)
   */
  function renderPeakHoursLineChart() {
    const container = document.querySelector('.chart-peak-hours-line');
    if (!container || container.dataset.rendered === 'true') return;

    let hours  = [];
    let totals = [];

    try {
      if (container.dataset.chartHours)  hours  = JSON.parse(container.dataset.chartHours);
      if (container.dataset.chartTotals) totals = JSON.parse(container.dataset.chartTotals);
    } catch (e) {
      console.error('Error al parsear datos en renderPeakHoursLineChart:', e);
      return;
    }

    container.dataset.rendered = 'true';
    container.innerHTML = '';

    const themeColors = getChartColors();

    const options = {
      series: [
        {
          name: 'Visitas',
          data: totals
        }
      ],
      chart: {
        type: 'line',
        height: 250,
        toolbar: { show: false },
        animations: { enabled: true, easing: 'easeinout', speed: 800 },
        dropShadow: {
          enabled: true,
          color: '#2563eb',
          top: 3,
          left: 2,
          blur: 4,
          opacity: 0.25
        }
      },
      colors: ['#2563eb'],
      dataLabels: {
        enabled: true,
        background: {
          enabled: true,
          color: '#1e293b',
          borderRadius: 6,
          padding: 5,
          borderWidth: 1,
          borderColor: '#334155',
          dropShadow: { enabled: true, top: 1, left: 1, blur: 2, opacity: 0.3 }
        },
        style: {
          fontSize: '11px',
          fontWeight: '700',
          colors: ['#ffffff']
        },
        offsetY: -6
      },
      stroke: {
        curve: 'smooth',
        width: 3.5
      },
      markers: {
        size: 7,
        colors: ['#f59e0b'],
        strokeColors: '#ffffff',
        strokeWidth: 2.5,
        hover: { size: 9 }
      },
      xaxis: {
        categories: hours,
        labels: {
          style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: { colors: themeColors.axisText, fontSize: '11px', fontWeight: 600 },
          formatter: (val) => Math.round(val)
        }
      },
      grid: { borderColor: themeColors.gridBorder, strokeDashArray: 4 },
      tooltip: {
        theme: themeColors.tooltipTheme,
        y: { formatter: (val) => `${val} visitas` }
      }
    };

    new ApexCharts(container, options).render();
  }

  function renderAllCharts() {
    if (typeof ApexCharts === 'undefined') {
      setTimeout(renderAllCharts, 100);
      return;
    }
    renderComboChart();
    renderWeekChart();
    renderUniquesComboChart();
    renderUniquesWeekChart();
    renderClicksDailyChart();
    renderTopLinksChart();
    renderSocialStackedCharts();
    renderPeakHoursLineChart();
  }

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.addedNodes.length) {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1 && (node.classList.contains('modal-overlay') || node.querySelector('.chart-summary-combo, .chart-summary-weeks, .chart-summary-uniques-combo, .chart-summary-uniques-weeks, .chart-summary-clicks-daily, .chart-summary-top-links, .chart-social-stacked, .chart-peak-hours-line'))) {
            setTimeout(renderAllCharts, 120);
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

  document.addEventListener('click', (e) => {
    if (e.target.closest('.modal-btn')) {
      setTimeout(renderAllCharts, 150);
    }
  });

  renderAllCharts();
}
