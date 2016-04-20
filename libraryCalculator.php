<!DOCTYPE html>
<html lang="en">
<head>
<title>Calculator sample project - AP Calc BC</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjs/1.2.0/math.min.js"></script><!-- Import math js library using cdn -->
<!-- Styling is under this -->
<style>
canvas {
    border: solid 1px #c0c0c0;
    margin-top: 20px;
}

body {
    margin: 0 auto;
    text-align: center;
    font-family: "Helvetica Neue", Arial, Tahoma, sans-serif;
}
#screenCover {
  width: 100%;
  left: 0;
  top: 0;
  position: fixed;
  height: 100%;
  background-color: black;
  -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
  filter: alpha(opacity=50);
  -moz-opacity: 0.5;
  opacity: 0.5;
  z-index:9;
  display:none;
}
.popup{position: absolute;width: 500px;height: auto;left: 50%;top: 20%;margin-left: -250px;background-color: white;border:solid 1px #c0c0c0;padding: 10px;border-radius: 10px 10px 0 0;z-index:10;}
.add {display:inline; color: #7f8c8d; text-decoration:none;}
.add:hover { font-weight:700;}
#add:hover { text-decoration:underline; color: #8e44ad;}
</style>
</head>
<body onLoad="init();">
<h1>AP Calculus BC Graphing Calculator</h1>
<!-- Layout and stuff. Button placement etc. -->
FTC: a: <input type = "text" id="aValue" /> b: <input type="text" id="bValue" /><input id="submitFTC" type="button" onclick="ftc()" value="calculate"> Value: <p style="display:inline" id="ftcValue"></p> a: <p style="display:inline" id="aAnswer"></p> b: <p style="display:inline" id="bAnswer"></p>
<br>
<a onclick="addEquation()" href="#">Add Equation</a>
<br>
<a onclick="deleteEquation()" href="#">Delete Equation </a>
<br>
<a onclick = "clearGraph()" href="#">Clear Equations </a>
<br>
<canvas id="graph" width="800" height="500"></canvas>
<div>
f(x) = 
<input type="text" id="functionString1" onkeypress="return enterCheck(event, this)" /><input id = "submit1" type="button" onclick="parseFn(this)" style="margin-left:10px;" value="Graph" />
<a id = "addOption1" class="add" href="javascript:$('#optionPane1').fadeToggle();$('#screenCover').fadeToggle();" onclick="changeWindow(this)">Option Pane </a><a id="cool1" class="add" href="#" onclick="coolify(this)">Coolify</a>
<div id="new">
</div>
<div id="optionPane1" name="optionPane" style="display:none" class="ui-draggable popup">
    Color: <input type = "text" id="color1" />
    Derivative Color: <input type= "text" id = "dcolor1" />
    2nd Derivative Color: <input type="text" id ="2dcolor1" />
</div>
<div id="screenCover" onclick="mapToggle()" style="display: none;"></div>
<script>
var WIDTH = 800;//number of pixels width
var HEIGHT = 500;// number of pixels height
var LINES = 20; //number of lines across and vertical
var STEP = .005;//increment for plugging in numbers
var c = document.getElementById("graph");
var ctx = c.getContext("2d");//purely syntax
var scale = 1;//zoom in and out. Never implemented
var color = "red";//default color
var counter = 1;//number of nodes created
var openWindow =0;//which window is open for graying
var points = new Array();//array of original function pixel points
var derivPoints = new Array();//array of derivative pixel points
var noDeriv = new Array();//array of original function values
var derArray = new Array();//array of derivative values
var secondDeriv = new Array();//array of second derrivative values
var secondPoints = new Array();//array of second derivative points
var holes = new Array();

function clearGraph(){
    ctx.clearRect(0, 0, WIDTH, HEIGHT);//clear the entire canvas
    init();//reinitialize the lines and stuff
}

function ftc(){
    var lowerBound= document.getElementById("aValue").value;//lower bound of integral
    var upperBound= document.getElementById("bValue").value;//upper bound of integral
    var arbitrary = 10/STEP; // to get to the origin. since 20 lines 10 / step gives u the number of points to origin
    var sum =0;//sum of reiman rectangles
    for(var i= arbitrary+lowerBound/STEP; i<arbitrary+upperBound/STEP;i++){//get to the point in the array that associates with that lowerbound number
        sum += derArray[1][i][0]*STEP;//multiple height by width and add up
    }
    document.getElementById("ftcValue").innerHTML = sum;//change the value in the dom to the value of sum
    var parsedString = document.getElementById("functionString1").value;//get the equation
    while(parsedString.indexOf("x")!=-1){
            parsedString = parsedString.replace("x",lowerBound);//replace x with value of lower bound
    }
    document.getElementById("aAnswer").innerHTML = math.eval(parsedString);//evaluate the string
    parsedString = document.getElementById("functionString1").value;
    while(parsedString.indexOf("x")!=-1){
        parsedString = parsedString.replace("x",upperBound);//replace x with value of upper bound
    }
    document.getElementById("bAnswer").innerHTML = math.eval(parsedString);// evaluate the string and set the dom item to that
}
function coolify(number){
    number = number.id.substr(4);//get the number through string manipulation
    for(var i=0; i< derArray[number].length-1; i++){
        if(derArray[number][i][0]<0){
            ctx.fillStyle = "green";//make decreasing values green
            ctx.fillRect(points[number][i][0],points[number][i][1],2,2);
        }else{
            ctx.fillStyle = "red";
            ctx.fillRect(points[number][i][0],points[number][i][1],2,2);//make increasing values red
        }
        if(Math.round(derArray[number][i][0]*10)/10===0){
            ctx.fillStyle = "black";// do some rounding make these zeroes black
            ctx.fillRect(points[number][i][0],points[number][i][1],5,5);
        }
    }
    for(var i=0; i<secondDeriv[number].length-1; i++){
        if(Math.round(secondDeriv[number][i][0]*10)/10===0){
            ctx.fillStyle = "black";
            ctx.fillRect(points[number][i][0],points[number][i][1],5,5);
        }
    }
}

function init() {
    $("#optionPane1").draggable();//make the optionpane draggable. jQuery!
    var AXIS_COLOR = "#707070";
    var LINE_COLOR = "#c0c0c0";//grayish colors
    ctx.fillStyle = LINE_COLOR;
    for (var i = 0; i < LINES; i++) {
        ctx.fillRect(WIDTH / LINES * i, 0, 1, HEIGHT);//fill axis lines
        ctx.fillRect(0, HEIGHT / LINES * i, WIDTH, 1);
    }
    ctx.fillStyle = AXIS_COLOR;
    ctx.fillRect(WIDTH / 2 - 2, 0, 4, HEIGHT);
    ctx.fillRect(0, HEIGHT / 2 - 2, WIDTH, 4);
}

function changeWindow(windowChanged){
    openWindow = windowChanged.id.substr(9);//make the openwindow the one that is open. set ids equal
}

function derivative(number){
    var tempColor = document.getElementById("dcolor" + number).value;//get the derivative color
    if(typeof(derivPoints[number])!="undefined"){//if there are derivative points for that number
        for(var i =0; i<derivPoints[number].length;i++){
            ctx.clearRect(derivPoints[number][i][0], derivPoints[number][i][1], 3, 3);
        }
    }
    derivPoints[number]= new Array();//empty derivPoints for that number
    derArray[number] = new Array();//empty deriv array for that number
    var slope;
    for(var i = 0; i < noDeriv[number].length-1; i++){
        slope=(noDeriv[number][i+1][1]-noDeriv[number][i][1])/(noDeriv[number][i+1][0]-noDeriv[number][i][0]);
        derArray[number].push([slope, noDeriv[number][i][0]]);//push the derivative values into the derivative array
    }
    plotArray(derArray[number], true, tempColor, number);//plot these values
    init();//make sure no lines be disappearing
}

function secondDerivative(number){
    var tempColor = document.getElementById("2dcolor" + number).value;//get 2nd derivative color
     if(derivPoints[number].length>5){//arbitrary value just check if there is stuff in the deriv points array
        if(typeof(secondPoints[number])!="undefined"){
            for(var i=0;i<secondPoints[number].length;i++){
                ctx.clearRect(secondPoints[number][i][0], secondPoints[number][i][1],3,3);//clear the old values
            }
        }
        secondPoints[number] = new Array();//empty secondPoints
        secondDeriv[number] = new Array();//empty secondDeriv
        var slope;
        for(var i=0; i< derArray[number].length-1; i++){
            slope = (derArray[number][i+1][0]-derArray[number][i][0])/(derArray[number][i+1][1]-derArray[number][i][1]);
            secondDeriv[number].push([slope, derArray[number][i][1]]);//fill secondDeriv with secondDeriv vallues
        }
        plotArray(secondDeriv[number], false, tempColor, number);//plot them values
    }else{
        alert("Do the first derivative first before doing the second");//if there is nothing in the first deriv than tell user to do first deriv first
    }
    init();//redraw axis lines
}
function plotArray(tempArray, derArray, tempColor, number){
    var x;
    var y;
    if(!tempColor){
        tempColor = "black";//if there is no color make it black
    }
    for(var i =0; i<tempArray.length; i++){
        x = tempArray[i][1] / scale;//scale is never really implemented
        y = tempArray[i][0] / scale;
        x = WIDTH * (.5 + x / LINES);//multiply width by half and width * x values / lines to plot x
        y = HEIGHT * (.5-y/LINES); //half of height - y values/ lines to plot y because y is decreasing in computer
        ctx.fillStyle = tempColor;//set fill color
        ctx.fillRect(x-1, y-1, 2, 2);//fill this point
        if(derArray){
            derivPoints[number].push([x-1,y-1]);//if derivative put in deriv points array
        }else{
            secondPoints[number].push([x-1,y-1]);//if second derivative put in second deriv points array
        }
    }
    init();// redraw axis

}
function plotPoint(x, y, color, number) {
    noDeriv[number].push([x,y]);//push x and y values into noDeriv
    x = x / scale;
    y = y / scale;
    x = WIDTH * (.5 + x / LINES);
    y = HEIGHT * (.5 - y / LINES);//convert points to pixels
    ctx.fillStyle = color;
    ctx.fillRect(x - 1, y - 1, 2, 2);
    points[number].push([x-1,y-1]);//put pixel points in points array
}

function genGraph(fxString, color, number) {
    var parsedString;
    var answer;
    points[number] = new Array();
    noDeriv[number] = new Array();
    for (var i = -10; i < 10; i += STEP) {//plug in i
        i = Math.round(i*1000)/1000;
        parsedString = fxString;
        while(parsedString.indexOf("x")!=-1){
            parsedString = parsedString.replace("x","("+i+")");
        }//replace all the x's with the i value
        answer = math.eval(parsedString);
        if(!answer){
            alert("There is a hole at x = "+ i);
        }else{
        plotPoint(i,answer,color, number);//plot those points
        }  
    }
    derivative(number);//than find derivative
    secondDerivative(number);//than find second derivative
}

function parseFn(number) {
    number = number.id.substr(6);
    if(typeof(points[number])!="undefined"){
        for(var i =0; i<points[number].length;i++){
            ctx.clearRect(points[number][i][0], points[number][i][1], 3, 3);//clear points of that function if they are already there
        }
    }
    init();//redraw axis
    var unparsed = document.getElementById("functionString"+number).value;//get equation
    var color = document.getElementById("color"+number).value;
    if(color == ""){
        color = "red";
    }
    genGraph(unparsed, color, number);//generate graph
}

function enterCheck(e,thing) {
    if (e.keyCode == 13) {
        parseFn(thing);
        return false;
    }//do parseFn if you press enter 
}

function mapToggle() {
    color = document.getElementById("color"+openWindow).value;
    document.getElementById("optionPane"+openWindow).style.display="none";
    $('#screenCover').fadeToggle();//change gray background of screen when optionpane pops up
}

function addEquation(){
        counter+=1;
        var newEquation= document.getElementById("functionString1").cloneNode(true);
        var newSubmit = document.getElementById("submit1").cloneNode(true);
        var newAdd = document.getElementById("addOption1").cloneNode(true);
        var newOption = document.getElementById("optionPane1").cloneNode(true);
        var newCoolify = document.getElementById("cool1").cloneNode(true);
        newEquation.id = ("functionString" + counter);
        newSubmit.id = "submit"+counter;
        newAdd.id = "addOption"+counter;
        newOption.id = "optionPane"+counter;
        newCoolify.id = "cool"+counter;//make specific ids
        newOption.firstChild.nextSibling.id = "color"+counter;
        newOption.firstChild.nextSibling.nextSibling.nextSibling.id="dcolor"+counter;
        newOption.firstChild.nextSibling.nextSibling.nextSibling.nextSibling.nextSibling.id = "2dcolor"+counter;
        newAdd.href="javascript:$('#optionPane"+counter+"').fadeToggle();$('#screenCover').fadeToggle();";
        newdiv = document.createElement('div');
        newdiv.id=("new"+counter);
        thing="new"+counter;
        document.getElementById("new").appendChild(newdiv);
        newEquation.setAttribute('name', 'equationInput');
        document.getElementById(thing).appendChild(document.createTextNode("f(x) = " ));
        document.getElementById(thing).appendChild(newEquation);
        document.getElementById(thing).appendChild(newSubmit);  
        document.getElementById(thing).appendChild(newAdd);
        document.getElementById(thing).appendChild(newCoolify);
        document.getElementById(thing).appendChild(newOption);
        $("#optionPane"+ counter).draggable();
        //add a new node for more equations
}
function deleteEquation(){
    if(counter!=1){
        var Start = document.getElementById('functionString'+counter);
        Start.parentNode.remove();
        counter -=1;//delete last node
    }
}
</script>
</body>
</html>