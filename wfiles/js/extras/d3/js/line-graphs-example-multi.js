function multiLineGraph () {
  var color = d3.scale.ordinal().range(colorbrewer.RdBu[9]);
  
  // Set margins and size
  var margin = {top: 20, right: 40, bottom: 30, left: 40},
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
      //.interpolate("basis")
      .x(function(d) { return x(d.timeinsec); })
      .y(function(d) { return y(d.speed); });

  // Create the SVG canvas
  var svg = d3.select(".svg-holder-multi").append("svg")
    .attr("viewBox", "0 0 485 400")
    .attr("preserveAspectRatio", "xMinYMin meet")
    .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  // Load in data and draw line graph
  d3.tsv("data/line-graphs-example-multi-data.tsv", function(error, data) {

    color.domain(d3.keys(data[0]).filter(function(key) { return key !== "timeinsec"; }));

    data.forEach(function(d) {
      d.timeinsec = d.timeinsec;
      //d.speed = d.speed;
    });

    var vehicles = color.domain().map(function(name) {
      return {
        name: name,
        values: data.map(function(d) {
          return {timeinsec: d.timeinsec, speed: +d[name]};
        })
      };
    });

    x.domain(d3.extent(data, function(d) { return d.timeinsec; }));

     y.domain([
      d3.min(vehicles, function(c) { return d3.min(c.values, function(v) { return v.speed; }); }),
      d3.max(vehicles, function(c) { return d3.max(c.values, function(v) { return v.speed; }); })
    ]);

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

    // Draw the lines
    var vehicle = svg.selectAll(".vehicle")
        .data(vehicles)
      .enter().append("g")
        .attr("class", function(d) {
          return "vehicle " + d.name;
        })
    
    function shadeColor(color, percent) {   
      var num = parseInt(color.slice(1),16), amt = Math.round(2.55 * percent), R = (num >> 16) + amt, G = (num >> 8 & 0x00FF) + amt, B = (num & 0x0000FF) + amt;
      return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 + (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255)).toString(16).slice(1);
    }
    
    vehicle.append("path")
        .attr("class", "line")
        .attr("d", function(d) { return line(d.values); })
        .style("stroke", function(d) {
          if (d.name == "V1") {
            return shadeColor(color(d.name), -30);
          }
          return color(d.name);
        })
        .style("stroke-width", function(d) {
          if (d.name == "V1") {
            return 4;
          }
          return 2;
        });
    
    

    // Draw the points
    vehicle.selectAll("circle")
        .data(function(d, i) { return d.values; })
      .enter().append("circle")
        .attr("class", "pointy")
        .style("stroke", "none")
        .style("fill", function(d) {
          if (d3.select(this.parentNode).datum().name == "V1") {
            return shadeColor(color(d3.select(this.parentNode).datum().name), -30);
          }
          return color(d3.select(this.parentNode).datum().name);
        })
        .attr("cx", function(d, i) { return x(d.timeinsec); })
        .attr("cy", function(d, i) { return y(d.speed); })
        .attr("r", function(d, i) {
          if (d3.select(this.parentNode).datum().name == "V1") {
            return 6;
          }
          return 3;
        });
  });
};

multiLineGraph();
