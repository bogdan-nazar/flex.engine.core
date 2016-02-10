<!--/*fet:%login-box%*/-->
	<div class="<!--/*fet:def:parent*/-->">
		<div class="<!--/*fet:def:section*/-->">
			<h2 class="head"><!--/*fet:var:%head%*/--></h2>
			<div class="inner">
				<div class="rel"><div class="sect-head"><!--/*fet:var:%title-login%*/--></div></div>
				<div class="fields">
					<div class="field">
						<span class="field-title"><!--/*fet:var:%title-logname%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-login-name" id="<!--/*fet:def:parent*/-->-login-name" type="text" class="field-input" title="<!--/*fet:var:%title-logname%*/-->" value="<!--/*fet:var:%login-name%*/-->" maxlength="32" />
					</div>
					<div>
						<span class="field-title"><!--/*fet:var:%title-password%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-login-pass" id="<!--/*fet:def:parent*/-->-login-pass" type="password" class="field-input" title="<!--/*fet:var:%title-password%*/-->" value="" maxlength="32" />
					</div>
				</div>
				<div class="btns">
					<span class="btn-link rmargin" onclick="<!--/*fet:def:parent*/-->.login()"><!--/*fet:var:%btn-login-title%*/--></span>
					<span class="btn-link"><!--/*fet:var:%btn-forgot-title%*/--></span>
					<span id="<!--/*fet:def:parent*/-->-forgot-waiter" class="waiter" style="display:none;"></span>
				</div>
				<div class="rel"><div class="sect-head"><!--/*fet:var:%title-register%*/--></div></div>
				<div class="fields">
					<div class="regnote"><!--/*fet:var:%regnote%*/--></div>
					<div class="field">
						<span class="field-title"><!--/*fet:var:%title-regname%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-reg-name" id="<!--/*fet:def:parent*/-->-reg-name" type="text" class="field-input" title="<!--/*fet:var:%title-regname%*/-->" value="<!--/*fet:var:%reg-name%*/-->" maxlength="32" />
					</div>
					<div class="field">
						<span class="field-title"><!--/*fet:var:%title-password%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-reg-pass" id="<!--/*fet:def:parent*/-->-reg-pass" type="password" class="field-input" title="<!--/*fet:var:%title-password%*/-->" value="" maxlength="32" />
					</div>
					<div class="field">
						<span class="field-title"><!--/*fet:var:%title-password-retype%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-reg-pass2" id="<!--/*fet:def:parent*/-->-reg-pass2" type="password" class="field-input" title="<!--/*fet:var:%title-password-retype%*/-->" value="" maxlength="32" />
					</div>
					<div class="field">
						<span class="field-title"><!--/*fet:var:%title-regdisplay%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-reg-display" id="<!--/*fet:def:parent*/-->-reg-display" type="text" class="field-input" title="<!--/*fet:var:%title-regdisplay%*/-->" value="<!--/*fet:var:%reg-display%*/-->" maxlength="32" />
					</div>
					<div>
						<span class="field-title"><!--/*fet:var:%title-regemail%*/-->:</span>
						<input name="<!--/*fet:def:parent*/-->-reg-email" id="<!--/*fet:def:parent*/-->-reg-email" type="text" class="field-input" title="<!--/*fet:var:%title-regemail%*/-->" value="<!--/*fet:var:%reg-email%*/-->" maxlength="32" />
					</div>
				</div>
				<div class="btns">
					<span class="btn-link" onclick="<!--/*fet:def:parent*/-->.register()"><!--/*fet:var:%btn-register-title%*/--></span>
				</div>
			</div>
		</div>
	</div>
<!--/*/fet:%login-box%*/-->
<!--/*fet:%logged-box%*/-->
	<div class="<!--/*fet:def:parent*/-->">
		<div class="<!--/*fet:def:section*/-->">
			<h2 class="head"><!--/*fet:var:%head%*/--></h2>
			<div class="inner">
				<div class="msg">
					<div class="msg-text"><!--/*fet:var:%msg%*/--></div>
					<a class="main-go" href="<!--/*fet:def:appRoot*/-->"><!--/*fet:var:%msg-go%*/--></a>
				</div>
			</div>
		</div>
	</div>
<!--/*/fet:%logged-box%*/-->