#slider {
  background-size: cover;
  xbackground-attachment: fixed;
  background-position: 10% 0%;
  background-color:#e1e1dd;
  padding: 60px 0 280px 0;
  position: relative;
  min-height:650px;
  background: url("/images/site/homepage.png") no-repeat;
}

#sliderx:before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to left, #8b86a3, #322e40);
  opacity: 0.3;
}
#slider h1 {
  font-weight: 100;
  line-height: 50px;
  letter-spacing: 4px;

}
#slider h2 {
  font-weight: 300;
  font-size: 45px
  letter-spacing: 4px;

}
.homepage_image{
	-webkit-background-size: cover;
	-moz-background-size: cover;
	-o-background-size: cover;
	background-size: cover;
    max-width: 100%;
    max-height: 100%;
    margin: auto;
    overflow: auto;
}
