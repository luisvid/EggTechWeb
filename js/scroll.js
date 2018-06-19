var pageHeight = window.innerHeight;
var isAnimating = false;
document.body.style.transform = 'translate3d(0px,0px,0px)';

document.addEventListener('scroll', function(e){
  document.body.scrollTop = 0;
});
document.addEventListener('wheel', wheelListener);

function wheelListener(e) {
  if(e.deltaY > 0) {
    scrollPage(-pageHeight);
  } else {
    scrollPage(+pageHeight);
  }
}

function scrollPage(scrollSize) {
  if(isAnimating){
    return;
  }
  isAnimating = true;
  var yPos = getNewYPos(scrollSize);
  document.body.style.transform = 'translate3d(0px,'+ yPos + ',0px)';
}

function getNewYPos(add){
  var oldYPos = document.body.style.transform.split(',')[1];
  oldYPos = parseInt(oldYPos.replace(/px/,''));
  var newYPos = oldYPos + add;
  if(newYPos > 0){
    isAnimating = false;
  }
  return Math.min(0, newYPos) + 'px';
}


document.body.addEventListener('transitionend', function(){
  setTimeout(function(){ isAnimating = false; }, 500);
  document.addEventListener('wheel', wheelListener);
})