
function simpleLineGraphInterpolation () {

// Set margins and size
var margin = {top: 20, right: 20, bottom: 30, left: 40},
    width = 485 - margin.left - margin.right,
    height = 400 - margin.top - margin.bottom;

//var parseDate = d3.time.format("%d-%b-%y").parse;

// Set size of x axis
var x = d3.scale.linear()
    .range([0, width]);

// Set size of y axis
var y = d3.scale.linear()
    .range([height, 0]);

// Set position of x axis
var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

// Set position of y axis
var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left");

var line = d3.svg.line()
    .interpolate("basis")
    .x(function(d) { return x(d.timeinsec); })
    .y(function(d) { return y(d.speed); });

// Create the SVG canvas
var svg = d3.select(".svg-holder-interpolation").append("svg")
  .attr("viewBox", "0 0 485 400")
  .attr("preserveAspectRatio", "xMinYMin meet")
  .append("g")
  .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

// Load in data and draw line graph
d3.tsv("data/line-graphs-example-simple-data.tsv", function(error, data) {

  data.forEach(function(d) {
    d.timeinsec = d.timeinsec;
    d.speed = d.speed;
  });

  x.domain(d3.extent(data, function(d) { return d.timeinsec; }));
  y.domain(d3.extent(data, function(d) { return d.speed; }));

  // Draw the x axis
  svg.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis);

  // Draw the y axis
  svg.append("g")
      .attr("class", "y axis")
      .call(yAxis)
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", ".71em")
      .attr("class", "label")
      .style("text-anchor", "end")
      .text("Speed (m/s)");

  // Draw the points
  svg.selectAll(".point")
      .data(data)
    .enter().append("circle")
      .attr("stroke", "orange")
      .attr("fill", function(d, i) { return "orange" })
      .attr("cx", function(d, i) { return x(d.timeinsec) })
      .attr("cy", function(d, i) { return y(d.speed) })
      .attr("r", function(d, i) { return 3 });

  // Draw the line
  svg.append("path")
      .datum(data)
      .attr("class", "line")
      .attr("d", line);

});

}

simpleLineGraphInterpolation();
