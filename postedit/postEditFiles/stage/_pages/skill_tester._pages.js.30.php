function showMap(img){
	var x = cursor.x - img.offsetLeft;
	var y = cursor.y - img.offsetTop;
	console.log(cursor.x+','+cursor.y+','+x+','+y);
}

function FindPosition(oElement)
{
  if(typeof( oElement.offsetParent ) != "undefined")
  {
    for(var posX = 0, posY = 0; oElement; oElement = oElement.offsetParent)
    {
      posX += oElement.offsetLeft;
      posY += oElement.offsetTop;
    }
      return [ posX, posY ];
    }
    else
    {
      return [ oElement.x, oElement.y ];
    }
}

function GetCoordinates(e){
  var PosX = 0;
  var PosY = 0;
  var ImgPos = FindPosition(getObject('gridimg'));
  if (!e){var e = window.event;}
  if (e.pageX || e.pageY){
    PosX = e.pageX;
    PosY = e.pageY;
  }
  else if (e.clientX || e.clientY){
      PosX = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
      PosY = e.clientY + document.body.scrollTop  + document.documentElement.scrollTop;
    }
  PosX = PosX - ImgPos[0];
  PosY = PosY - ImgPos[1];
  setText('xy',PosX+','+PosY);
}

function showMapPoints(){
	var myImg = document.getElementById('gridimg');
	myImg.onmousemove = GetCoordinates;
}
