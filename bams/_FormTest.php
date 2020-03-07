<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Untitled Document</title>

<script type="text/javascript" src="assets/javascript/focus_field.js"></script>
<script type="text/javascript">
window.onload = initFormFieldFocus;
function initFormFieldFocus()
{
	focusField(document.getElementById("fN"));

	return true;
}
</script>

</head>
<body>
		<h2>
			Form Field Focus
		</h2>
		<p>
			The JavaScript on this page attempts to focus the first form field once the page has loaded. However, if any content has been added to the other form fields it doesn't try to focus, so you don't get the user typing into the wrong field midway through a repsonse.
		</p>
		<p>
			Even better, in Internet Explorer if the user has merely <em>focused</em> on another field, the script will not try to re-focus the cursor.
		</p>
		<form id="TheForm">
			<fieldset>
				<legend>
					Submit top secret espionage
				</legend>
				<label for="firstName">
					First name:
					<input id="fN" name="firstName" class="text" type="text" />
				</label>
				<label for="lastName">
					Last name:
					<input id="lastName" name="lastName" class="text" type="text" />
				</label>
				<label for="title">
					Title:
					<select id="title" name="title">
						<option value="1">Jester</option>
						<option value="2">Comptroller</option>
						<option value="3">Super Nintendo</option>
						<option value="4">Cleaner of the monkey house</option>
					</select>
				</label>
				<label for="email">
					E-mail address:
					<input id="email" name="email" class="text" type="text" />
				</label>
				<label for="espionage">
					Encrypted espionage:
					<textarea id="espionage" name="espionage"></textarea>
				</label>
				<label for="check">
					I wish to be e-mailed about everything:
					<input id="check" name="check" class="checkbox" type="checkbox" value="argh" />
				</label>
				<input class="submit" type="submit" value="Submit" />
			</fieldset>
</form>
</body>
</html>
