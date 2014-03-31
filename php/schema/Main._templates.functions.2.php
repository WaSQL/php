function templateCurrentMenu($name){
  global $PAGE;
  if($PAGE['name']==$name){return 'current_page_item';}
  return '';
}
