
function addToCart(i){
	var request_data = 'pid_list[]='+i+'';
	jsonPost('send-information.php',request_data,function(j){
			var cart = localStorage.cart;
			if(cart == undefined){
				cart = {};
			}
			else {
				cart = JSON.parse(cart);
			}
			if(cart[i] == undefined){
				cart[i] = {'quantity':0};
			}
			cart[i].name = j[i]["name"];
			cart[i].price = j[i]["price"];
			cart[i].quantity = cart[i].quantity + 1;
			localStorage.cart = JSON.stringify(cart);
			updateCart();
		//}
	});
}

function sendRequest(url,request,isGet,callBack){

	var xhr = (window.XMLHttpRequest)
			? new XMLHttpRequest()
			: new ActiveObject("Microsoft.XMLHTTP"),
		async = true;
	xhr.onreadystatechange = function(){
		if(xhr.readyState == 4 && xhr.status == 200){
			var text = JSON.parse(xhr.responseText);
			if(callBack){
				callBack(text);
			}
		}
	};
	if(isGet){
		var parm = [];
		for(var i in request){
			parm.push(encodeURIComponent(i) + "="+ encodeURIComponent(request[i]));
		}
		parm = parm.join('&');
		xhr.open("GET",url + '?' + parm);
		xhr.send();
	}
	else{
		xhr.open("POST",url);
		xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		// request = JSON.stringify(request);
		xhr.send(request);
	}


}
function jsonPost(url,request,callBack){
	sendRequest(url,request,false,callBack);
}

function clearCart(){
	localStorage.clear();
	updateCart();
}


// update cart after each change 
function updateCart(){
	if(localStorage.cart != undefined){
		var request_data= '';
		var total = 0;
		var shoppingCart ='<table><tr><th>Product</th><th>Price</th><th>Quantity</th></tr>';
		var form = '<form id="shoppingform" method="POST" action="https://www.sandbox.paypal.com/cgi-bin/webscr"><input type="hidden" name="cmd" value="_cart">';
	    form += '<input type="hidden" name="upload" value="1"><input type="hidden" name="business" value="0">';
	    form += '<input type="hidden" name="currency_code" value=""><input type="hidden" name="charset" value="utf-8" />';
	    form += '<input type="hidden" name="submitcart" value='+nonce+' />';
    var num = 1;
		jsonPost('send-information.php',request_data,function(j){
			var cart = localStorage.cart;
			if(cart == undefined){
				cart = {};
			}
			else {
				cart = JSON.parse(cart);
			}
			for(var i in cart){
				if(cart[i] == undefined){
					cart[i] = {'quantity':0};
				}
				cart[i].name = j[i]["name"];
				cart[i].price = j[i]["price"];
				cart[i].quantity = cart[i].quantity;
				localStorage.cart = JSON.stringify(cart);
				shoppingCart +='<tr><td>'+ cart[i].name + '</td><td> $' + cart[i].price + '</td><td><input type="number" value=' + cart[i].quantity + ' onchange = "updateProduct(' + i + ', this.value)">  </td> </tr>';
				total += cart[i].price * cart[i].quantity;
				form += '<input type="hidden" name="item_name_'+num+'" value='+cart[i].name+' />';
		    	form += '<input type="hidden" name="item_number_'+num+'" value='+i+' />';
		    	form += '<input type="hidden" name="quantity_'+num+'" value='+cart[i].quantity+' />';
		    	form += '<input type="hidden" name="amount_'+num+'" value='+cart[i].price+' />';
		    	num++;
			}
			shoppingCart += "</table><br>";
			form += '<input type="hidden" name="custom" value="0"/><input type="hidden" name="invoice" value="0"/>';
 			shoppingCart += form;
		    total = parseFloat(total);
		    total = total.toFixed(2);
		    shoppingCart += "Total: $" + total;
		    shoppingCart += '<br><br><input id="checkout" type="button" onclick="tobeSubmit('+total+')" value="Checkout" />';
		    document.getElementById("sltable").innerHTML = shoppingCart;
			
		});

	}
	else return;
}



 //    var form = '<form id="shoppingform" method="POST" action="https://www.sandbox.paypal.com/cgi-bin/webscr"><input type="hidden" name="cmd" value="_cart">';
 //    form += '<input type="hidden" name="upload" value="1"><input type="hidden" name="business" value="0">';
 //    form += '<input type="hidden" name="currency_code" value=""><input type="hidden" name="charset" value="utf-8" />';
 //    form += '<input type="hidden" name="submitcart" value='+nonce+' />';
 //    var num = 1;
 //    for (var i in cart){
 //    	form += '<input type="hidden" name="item_name_'+num+'" value='+cart[i].name+' />';
 //    	form += '<input type="hidden" name="item_number_'+num+'" value='+i+' />';
 //    	form += '<input type="hidden" name="quantity_'+num+'" value='+cart[i].quantity+' />';
 //    	form += '<input type="hidden" name="amount_'+num+'" value='+cart[i].price+' />';
 //    	num++;
 //    }

 //    form += '<input type="hidden" name="custom" value="0"/><input type="hidden" name="invoice" value="0"/>';
 //    shoppingCart += form;
 //    shoppingCart += '<br><br><button id="checkout" onclick="tobeSubmit('+total+')">Checkout</button>';
 //    shoppingCart += '</form>';
 //    document.getElementById("sltable").innerHTML = shoppingCart;


// for update products after change of number 
function updateProduct(i,quantity){
	var cart = JSON.parse(localStorage.cart);
	if(quantity < 0){
		alert("Please select a positive number");
	}
	else if (quantity > 0){
		cart[i].quantity = quantity;
		localStorage.cart = JSON.stringify(cart);
	}
	else if (quantity == 0){
		delete cart[i];
		localStorage.cart = JSON.stringify(cart);
	}
	else {
		alert("Please enter a number");
	}
	updateCart();
}

// for remove products if clicking on the remove button
function removeProduct(i){
	if(localStorage.cart!= undefined){
		var cart = JSON.parse(localStorage.cart);
	}
	else return;
	delete cart[i];
	localStorage.cart = JSON.stringify(cart);
	updateCart();
}




function tobeSubmit(total){
	if(total<=0){
		alert("There is nothing in the shopping cart");
	} else{
			updateCart();
			var cart = JSON.parse(localStorage.cart)
			var response = {};
			var xhr = (window.XMLHttpRequest)
				? new XMLHttpRequest()
				: new ActiveObject("Microsoft.XMLHTTP"),
			async = true;
			xhr.onreadystatechange = function(){
				if(xhr.readyState == 4 && xhr.status == 200){
					var text = JSON.parse(xhr.responseText);
				if(text.success)
				{
					var form = document.getElementById('shoppingform');
					form.elements.namedItem("invoice").value = text.success.order_id;
					form.elements.namedItem("custom").value = text.success.digest;
					form.elements.namedItem("business").value = text.success.merchant_email;
					form.elements.namedItem("currency_code").value = "HKD";
					form.submit();
					clearCart();}
					else{alert(text.failed);}
				}
			};
			xhr.open('POST',"checkout-process.php",true);
			xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			for(var i in cart){
				response[i] = cart[i].quantity;
			}
			response = JSON.stringify(response);
			response = "cart="+response+"&nonce="+nonce;
			xhr.send(response);

		}
}

updateCart();
	
