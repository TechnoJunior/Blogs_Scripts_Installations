'use strict';$(function(){$('.module-sshot').not('.current-theme .module-sshot').each(function(){const bar=$('<div>').addClass('bloc-toggler');$(this).after($(bar).toggleWithLegend($(this).parent().children('.toggle-bloc'),{img_on_txt:dotclear.img_plus_theme_txt,img_on_alt:dotclear.img_plus_theme_alt,img_off_txt:dotclear.img_minus_theme_txt,img_off_alt:dotclear.img_minus_theme_alt,legend_click:true}));$(this).children('img').on('click',function(){$(this).parent().parent().children('.bloc-toggler').trigger('click');});});$('div.modules-search form input[type=submit]').on('click',function(){const mlen=$('input[name=m_search]',$(this).parent()).val();if(mlen.length>2){return true;}else{return false;}});$('.checkboxes-helpers').each(function(){dotclear.checkboxesHelpers(this);});$('.modules-form-actions').each(function(){const rxActionType=/^[^\[]+/;const rxActionValue=/([^\[]+)\]$/;const checkboxes=$(this).find('input[type=checkbox]');$('input[type=submit]',this).on('click',function(){const keyword=$(this).attr('name');if(!keyword){return true;}
const maction=keyword.match(rxActionType);const action=maction[0];const mvalues=keyword.match(rxActionValue);if(!mvalues){let checked=false;if(checkboxes.length>0){$(checkboxes).each(function(){if(this.checked){checked=true;}});if(!checked){return false;}}
if(action=='delete'){return window.confirm(dotclear.msg.confirm_delete_themes);}}else{const module=mvalues[1];if(action=='delete'){return window.confirm(dotclear.msg.confirm_delete_theme.replace('%s',module));}}
return true;});});});