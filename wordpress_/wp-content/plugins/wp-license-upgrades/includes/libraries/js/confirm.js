function wplconfirm(){
	var x = confirm("Are you sure?");
	if (x)
		return true;
	else
		return false;
}

function wplPremium() {
	var x = confirm("You must be a Vip member! Sign Up Now?");
	if (x)
		window.open('https://wplicense.com/product/subscription/', '_blank');
	else
		return false;
};