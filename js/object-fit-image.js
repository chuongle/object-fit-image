$(window).load(function() {
  $('.object-fit').each(function(index) {
    resizeObjectFitImage($(this));
  });
  $(window).on('resize', function() {
    $('.object-fit').each(function(){
     resizeObjectFitImage($(this));
    });
  })
})
function resizeObjectFitImage(selector) {
  var wrapper_selector = drupalSettings.parent_wrapper;
  var wrapper = $(selector).closest('[data-image="object-fit"]');
  var wrapper_height = $(wrapper).outerHeight();
  // set height for image to match height of container
  $(selector).height(wrapper_height);
  // hide original background image
  $(wrapper).addClass('object-fit-container');
}