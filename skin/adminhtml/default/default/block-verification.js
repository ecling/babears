
(function($){

    $(document).ready(function(){
        function blockVerification(id)
        {
          $('#'+id).removeClass('required-entry');
          if($('#'+id)[0].tagName=='SELECT')
          {
              $('#'+id).removeClass('validate-select');
              
          }
          $('[for='+id+'] .required').hide();
        }

        blockVerification('telephone');
        blockVerification('region');
        blockVerification('region_id');
        $('#country_id').change(function(){
            blockVerification('region');
            blockVerification('region_id');
        })
      
            $('#region_id').removeClass('required-entry');
            $('[for=region_id] .required').hide();
    })
})(jQuery)