const communityLabels = communityData.slice(0, 8).map((item) => item.tag);
const communityValues = communityData.slice(0, 8).map((item) => parseInt(item.count));

const personalLabels = userData.slice(0, 8).map((item) => item.tag);
const personalValues = userData.slice(0, 8).map((item) => parseInt(item.score));

const ctxCommunity = document.getElementById('communityChart').getContext('2d');
const communityChart = new Chart(ctxCommunity, {
    type: 'bar',
    data: {
        labels: communityLabels,
        datasets: [
            {
                label: 'Số bài viết',
                data: communityValues,
                backgroundColor: [
                    'rgba(0, 184, 148, 0.7)',
                    'rgba(74, 144, 226, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(255, 107, 107, 0.7)',
                    'rgba(156, 39, 176, 0.7)',
                    'rgba(255, 152, 0, 0.7)',
                    'rgba(0, 150, 136, 0.7)',
                    'rgba(233, 30, 99, 0.7)',
                ],
                borderColor: ['#00b894', '#4a90e2', '#ffc107', '#ff6b6b', '#9c27b0', '#ff9800', '#009688', '#e91e63'],
                borderWidth: 2,
                borderRadius: 8,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: 12,
                    },
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                },
            },
            x: {
                ticks: {
                    font: {
                        size: 11,
                        weight: 'bold',
                    },
                },
                grid: {
                    display: false,
                },
            },
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14,
                },
                bodyFont: {
                    size: 13,
                },
                callbacks: {
                    label: function (context) {
                        return 'Số bài viết: ' + context.parsed.y;
                    },
                },
            },
        },
    },
});

const ctxPersonal = document.getElementById('personalChart').getContext('2d');
const personalChart = new Chart(ctxPersonal, {
    type: 'bar',
    data: {
        labels: personalLabels,
        datasets: [
            {
                label: 'Điểm tương tác',
                data: personalValues,
                backgroundColor: [
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(0, 184, 148, 0.7)',
                    'rgba(74, 144, 226, 0.7)',
                    'rgba(255, 107, 107, 0.7)',
                    'rgba(156, 39, 176, 0.7)',
                    'rgba(255, 152, 0, 0.7)',
                    'rgba(0, 150, 136, 0.7)',
                    'rgba(233, 30, 99, 0.7)',
                ],
                borderColor: ['#ffc107', '#00b894', '#4a90e2', '#ff6b6b', '#9c27b0', '#ff9800', '#009688', '#e91e63'],
                borderWidth: 2,
                borderRadius: 8,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        size: 12,
                    },
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                },
            },
            x: {
                ticks: {
                    font: {
                        size: 11,
                        weight: 'bold',
                    },
                },
                grid: {
                    display: false,
                },
            },
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14,
                },
                bodyFont: {
                    size: 13,
                },
                callbacks: {
                    label: function (context) {
                        return 'Điểm tương tác: ' + context.parsed.y;
                    },
                },
            },
        },
    },
});
