function formatCountry(a){return a.id?$('<div class="c-flag c-flag--'+a.element.value.toLowerCase()+'"></div> <span>'+a.text+"</span>"):a.text}function formatRole(a){if(!a.id||void 0===a.element)return a.text;var b=a.element.getAttribute("data-icon");return null===b?a.text:$('<i class="fa '+b+'"></i> <span>'+a.text+"</span>")}$(document).ready(function(){$(".js-select__country").select2({templateResult:formatCountry,templateSelection:formatCountry})}),$(document).ready(function(){$(".js-select__role").select2({templateResult:formatRole,templateSelection:formatRole,width:"100%"})}),$(document).ready(function(){$(".js-select__timezone").select2()});