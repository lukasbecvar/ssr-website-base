/* visitors metrics charts function */
// import apexcharts library
import ApexCharts from 'apexcharts'
document.addEventListener("DOMContentLoaded", function () {
    // get visitor metrics data from global scope
    const visitorMetrics = window.visitorMetrics

    // compute min and max values from the data for integer tick configuration
    const dataValues = Object.values(visitorMetrics)

    // configuration for chart with integer-only y-axis ticks and background grid
    const options = {
        series: [{
            name: 'Visitors',
            data: dataValues
        }],
        chart: {
            height: 275,
            type: 'area',
            zoom: {
                enabled: false
            },
            background: 'transparent',
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: 2,
            curve: 'smooth',
            colors: ['#818cf8']
        },
        markers: {
            size: 4,
            colors: ['#818cf8'],
            strokeColors: '#1a202c',
            strokeWidth: 2,
            hover: {
                size: 6
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#818cf8'],
                inverseColors: true,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 100]
            }
        },
        title: {
            align: 'left',
            style: {
                color: '#e2e8f0',
                fontSize: '18px',
                fontWeight: '600',
                fontFamily: 'Verdana, sans-serif'
            }
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px',
                fontFamily: 'Verdana, sans-serif'
            },
            x: {
                show: false
            },
            marker: {
                show: true
            },
            y: {
                formatter: function(value) {
                    return Math.round(value) + ' visitors'
                },
                title: {
                    formatter: () => ''
                }
            }
        },
        grid: {
            show: true,
            borderColor: 'rgba(100, 116, 139, 0.2)',
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: false
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            }
        },
        xaxis: {
            categories: Object.keys(visitorMetrics),
            labels: {
                style: {
                    colors: '#94a3b8',
                    fontFamily: 'Verdana, sans-serif',
                    fontSize: '12px'
                },
                rotate: 0,
                maxHeight: 50,
                hideOverlappingLabels: true
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            tickAmount: 'dataPoints'
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#94a3b8',
                    fontFamily: 'Verdana, sans-serif',
                    fontSize: '12px'
                },
                formatter: (value) => Math.round(value)
            },
            min: 0,
            forceNiceScale: true
        }
    }

    // render the chart element
    const chart = new ApexCharts(document.querySelector("#chart"), options)
    chart.render()
})
