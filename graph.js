// based on prepared DOM, initialize echarts instance
var myChart = echarts.init(document.getElementById('main'));

// specify chart configuration item and data
function randomData() {
now = new Date(+now + oneDay);
value = value + Math.random() * 21 - 10;
return {
name: now.toString(),
value: [
    [now.getFullYear(), now.getMonth() + 1, now.getDate()].join('/'),
    Math.round(value)
]
};
}

var data = [];
var now = +new Date(2020, 1, 1);
var oneDay = 24 * 3600 * 1000;
var value = Math.random() * 1000;
for (var i = 0; i < 1000; i++) {
data.push(randomData());
}

option = {
title: {
text: ''
},
toolbox: {
    feature: {
        saveAsImage: {},
        //dataView: {},
        dataZoom: {}
    },
    tooltip: {confine: true}
},
legend: {
    show: true,
    data: []
},
tooltip: {
    trigger: 'axis'
},
xAxis: {
    type: 'time',
    splitLine: {
        show: false
    }
},
yAxis: {
    type: 'value',
    boundaryGap: [0, 0],
    splitLine: {
        show: false
    }
},
series: []
};

/*
setInterval(function () {

for (var i = 0; i < 5; i++) {
data.shift();
data.push(randomData());
}

myChart.setOption({
series: [{
    data: data
}]
});
}, 1000); */

// use configuration item and data specified to show chart
myChart.setOption(option);