function onDestinationSelectChange() {
    for (let el of document.getElementById('destinationselect').options) {
        if (!el.selected)
            $('.' + el.value).hide();
        else
            $('.' + el.value).show();
    }
}

function buildSparkLine(dstElement, data, suggestedMin, suggestedMax) {
    var ctx = document.getElementById(dstElement).getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [...Array(data.length).keys()],
            datasets: [{data: data}]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {legend: {display: false}},
            elements: {line: {borderWidth: 2}, point: {radius: 0}},
            tooltips: {
                enabled: false
            },
            scales: {
                x: {display: false},
                y: {
                    suggestedMin: Math.floor(suggestedMin),
                    suggestedMax: Math.ceil(suggestedMax),
                    ticks: {
                        callback: function (label, index, labels) {
                            return label;
                        },
                        maxTicksLimit: 2,
                        autoSkipPadding: 0
                    }
                }
            }
        }
    })
    ;
}

$().ready(() => {
    $('#destinationselect').on('change', onDestinationSelectChange);
    onDestinationSelectChange();
})

