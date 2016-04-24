/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@principle-works.jp so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future. If you wish to customize it for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Localize
 * @package    Rack_Jp_Core
 * @copyright  Copyright (c) 2014 Veriteworks Inc. (http://principle-works.jp/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
try{
    window.formatCurrency = function formatCurrency(price, format, showPlus){
        var precision = isNaN(format.precision = Math.abs(format.precision)) ? 2 : format.precision;
        var requiredPrecision = isNaN(format.requiredPrecision = Math.abs(format.requiredPrecision)) ? 2 : format.requiredPrecision;

        precision = requiredPrecision;

        var integerRequired = isNaN(format.integerRequired = Math.abs(format.integerRequired)) ? 1 : format.integerRequired;

        var decimalSymbol = format.decimalSymbol == undefined ? "," : format.decimalSymbol;
        var groupSymbol = format.groupSymbol == undefined ? "." : format.groupSymbol;
        var groupLength = format.groupLength == undefined ? 3 : format.groupLength;

        var s = '';

        if (showPlus == undefined || showPlus == true) {
            s = price < 0 ? "-" : ( showPlus ? "+" : "");
        } else if (showPlus == false) {
            s = '';
        }

        switch (method) {
            case 'ceil' :
                price = Math.ceil(price);
                break;
            case 'floor' :
                price = Math.floor(price);
                break;
            case 'round' :
                //price = Math.round(price, precision);
                break;
        }

        var i = parseInt(price = Math.abs(+price || 0).toFixed(precision)) + "";
        var pad = (i.length < integerRequired) ? (integerRequired - i.length) : 0;
        while (pad) {
            i = '0' + i;
            pad--;
        }
        j = (j = i.length) > groupLength ? j % groupLength : 0;
        re = new RegExp("(\\d{" + groupLength + "})(?=\\d)", "g");

        var r = (j ? i.substr(0, j) + groupSymbol : "") + i.substr(j).replace(re, "$1" + groupSymbol) + (precision ? decimalSymbol + Math.abs(price - i).toFixed(precision).replace(/-/, 0).slice(2) : "")
        var pattern = '';
        if (format.pattern.indexOf('{sign}') == -1) {
            pattern = s + format.pattern;
        } else {
            pattern = format.pattern.replace('{sign}', s);
        }

        return pattern.replace('%s', r).replace(/^\s\s*/, '').replace(/\s\s*$/, '');
    };
} catch (e) {

}


if(typeof(Product.Config)!="undefined"){
    Product.Config.prototype=Object.extend(Product.Config.prototype,{
        formatPrice: function(price, showSign)
        {
            var str = '';

            if(showSign){
                if(price<0){
                    str+= '-';
                    price = -price;
                }
                else{
                    str+= '+';
                }
            }

            var re = RegExp('/(￥|円|Yen)/', 'i');

            if(this.priceTemplate.template.match(re) == -1) {
                jpCorePrecision = 2;
            }

            var roundedPrice = (Math.round(price*100)/100).toString();
            if(jpCorePrecision==0){
                price = parseInt(roundedPrice);
            } else {
                price = parseFloat(roundedPrice);
            }
            if (this.prices && this.prices[roundedPrice]) {
                str+= this.prices[roundedPrice];
            }
            else {
                precision=0;

                if(typeof(optionsPrice)!="undefined")
                    if(typeof(optionsPrice.priceFormat)!="undefined")
                        precision=optionsPrice.priceFormat.requiredPrecision;

                if(precision>0)str+= this.priceTemplate.evaluate({
                    price:price.toFixed(precision)
                    });
                else{
                    str+= this.priceTemplate.evaluate({
                        price:price
                    });
                }
            }

            return str;
        }


    });
}

