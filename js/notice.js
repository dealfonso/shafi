$(function() {
    $(".alert-success").on("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend",
      // DDN_Notice
      function(event) {
        if (event.originalEvent.propertyName == 'visibility') {
          this.style.display = "none";
      }    
    });
});
