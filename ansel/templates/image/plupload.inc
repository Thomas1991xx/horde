<div id="anseluploader"></div>
<div class="uploadnotification">
  <a class="twitterbutton button hidden" id="twitter"><?php echo _("Tweet") ?></a>
</div>
<?php echo $nojs; ?>
<h3><?php echo _("Photos in this gallery") . ' (' . $gallery->countImages() . ')' ?></h2>
<div id="horizontal_carousel">
  <div class="previous_button"></div>
  <div class="container">
   <ul></ul>
   <div id="spinner" style="display: none;"><?php echo _("Loading ...") ?></div>
  </div>
  <div class="next_button"></div>
</div>