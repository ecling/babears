"use strict";document.observe("dom:loaded",function(){var e=document.querySelector("#payment_ebanx_settings_iof_local_amount");if(e){e.addEventListener("change",function(e){confirm("You need to validate this change with EBANX, only deselecting or selecting the box will not set this to your customer. Contact your EBANX Account Manager or Business Development Expert.")||(e.target.value=1-e.target.value)})}});
//# sourceMappingURL=iof-option.js.map
