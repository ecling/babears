<?php

class Magestore_Fblogin_Block_Adminhtml_System_Configuration_Implementcode extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
       // $layout  =  Mage::helper('sociallogin')->returnlayout();
        //$block = Mage::helper('sociallogin')->returnblock();
        //$text =  Mage::helper('sociallogin')->returntext();
       // $template = Mage::helper('sociallogin')->returntemplate();
        return '
<div class="entry-edit-head collapseable"><a onclick="Fieldset.toggleCollapse(\'sociallogin_template\'); return false;" href="#" id="sociallogin_template-head" class="open">Code Implementation</a></div>
<input id="sociallogin_template-state" type="hidden" value="1" name="config_state[sociallogin_template]">
<fieldset id="sociallogin_template" class="config collapseable" style="">
<h4 class="icon-head head-edit-form fieldset-legend">Code for Facebook Login</h4>
<div id="messages">
    <ul class="messages">
        <li class="success-msg">
            <ul>
                <li>'.Mage::helper('fblogin')->__('You can put Facebook login button block in any preferred position by using these following codes. Please note that Facebook login buttons still work normally according to your settings in General Configuration tab if codes are not implemented.').'</li>				
            </ul>
        </li>
    </ul>
</div>
<div id="messages">
    <ul class="messages">
        <li class="notice-msg">
            <ul>
                <li>'.Mage::helper('fblogin')->__('Add code below to a template file').'</li>				
            </ul>
        </li>
    </ul>
</div>
<br>
<ul>
	<li>
		<code>
			&lt;?php echo $this->getLayout()->createBlock("fblogin/fblogin")->setTemplate("fblogin/bt_fblogin.phtml")->toHtml(); ?&gt;
		</code>
	</li>
</ul>
<br>
<div id="messages">
    <ul class="messages">
        <li class="notice-msg">
            <ul>
                <li>'.Mage::helper('fblogin')->__('You can put a social login button block on a CMS page.').'</li>				
            </ul>
        </li>
    </ul>
</div>
<br>
<ul>
	<li>
		<code>
			

{{block type="fblogin/fblogin" name="buttons.fblogin" template="fblogin/bt_fblogin.phtml" }}
		</code>
	</li>
</ul>
<br>
<div id="messages">
    <ul class="messages">
        <li class="notice-msg">
            <ul>
                <li>'.Mage::helper('fblogin')->__('Please copy and paste the code below to one of xml layout files where you want to show the Facebook button block.').'</li>				
            </ul>
        </li>
    </ul>
</div>

<ul>
	<li>
		<code>
		 &lt;block type="fblogin/fblogin" name="fblogin.fblogin" template="fblogin/bt_fblogin.phtml"&gt;
		 &lt;/block&gt;
		</code>	
	</li>
</ul>
<br>

</fieldset>';
    }
    
    
}
