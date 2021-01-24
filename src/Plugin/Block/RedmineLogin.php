<?php

namespace Drupal\robco_rest\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Redmine Cart' Block.
 *
 * @Block(
 *   id = "redmine_login_block",
 *   admin_label = @Translation("Redmine login block"),
 *   category = @Translation("Redmine"),
 * )
 */
class RedmineLogin extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tag = '
    <style type="text/css">
    .social-btn {
    display: inline-block;
    width: 2.25rem;
    height: 2.25rem;
    -webkit-transition: border-color 0.25s ease-in-out,background-color 0.25s ease-in-out,color 0.25s ease-in-out;
    transition: border-color 0.25s ease-in-out,background-color 0.25s ease-in-out,color 0.25s ease-in-out;
    border: 1px solid #e7e7e7;
    border-radius: 50%;
    background-color: #fff;
    color: #545454;
    text-align: center;
    text-decoration: none;
    line-height: 2.125rem;
    vertical-align: middle;
}
.form-control {
    display: block;
    width: 100%;
    height: calc(1.5em + 1rem + 2px);
    padding: .5rem 1rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #404040;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #e1e1e1;
    border-radius: 0;
    -webkit-transition: border-color 0.2s ease-in-out,-webkit-box-shadow 0.2s ease-in-out;
    transition: border-color 0.2s ease-in-out,-webkit-box-shadow 0.2s ease-in-out;
    transition: border-color 0.2s ease-in-out,box-shadow 0.2s ease-in-out;
    transition: border-color 0.2s ease-in-out,box-shadow 0.2s ease-in-out,-webkit-box-shadow 0.2s ease-in-out;
}
.input-group>.form-control, .input-group>.form-control-plaintext, .input-group>.custom-select, .input-group>.custom-file {
    position: relative;
    -webkit-box-flex: 1;
    -ms-flex: 1 1 auto;
    flex: 1 1 auto;
    width: 1%;
    margin-bottom: 0;
}
.input-group-text {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    padding: .5rem 1rem;
    margin-bottom: 0;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #404040;
    text-align: center;
    white-space: nowrap;
    background-color: #fff;
    border: 1px solid #e1e1e1;
}
    </style>
    <div class="container pb-5 mb-sm-4">
    <div class="row pt-5">
        <div class="col-md-6 pt-sm-3">
            <div class="card">
                <div class="card-body">
                    <h2 class="h4 mb-1">Sign in</h2>
                    <div class="d-sm-flex align-items-center py-3">
                        <h3 class="h6 font-weight-semibold opacity-70 mb-3 mb-sm-2 mr-sm-3">With social account:</h3>
                        <div>
                            <a class="social-btn sb-facebook mr-2 mb-2" href="#" data-toggle="tooltip" title="" data-original-title="Sign in with Facebook">
                                <i class="fa fa-facebook"></i>
                            </a>
                            <a class="social-btn sb-twitter mr-2 mb-2" href="#" data-toggle="tooltip" title="" data-original-title="Sign in with Twitter">
                                <i class="fa fa-twitter"></i> 
                            </a>
                            <a class="social-btn sb-linkedin mr-2 mb-2" href="#" data-toggle="tooltip" title="" data-original-title="Sign in with LinkedIn">
                                <i class="fa fa-linkedin"></i>
                            </a>
                        </div>
                    </div>
                    <hr>
                    <h3 class="h6 font-weight-semibold opacity-70 pt-4 pb-2">Or using form below</h3>
                    <form class="needs-validation" novalidate="" method="post" action="/robco_rest/login">
                        <div class="input-group form-group">
                            <div class="input-group-prepend"><span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg></span></div>
                            <input class="form-control" type="email" placeholder="Email" required="">
                            <div class="invalid-feedback">Please enter valid email address!</div>
                        </div>
                        <div class="input-group form-group">
                            <div class="input-group-prepend"><span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span></div>
                            <input class="form-control" type="password" placeholder="Password" required="">
                            <div class="invalid-feedback">Please enter valid password!</div>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" checked="" id="remember_me">
                                <label class="custom-control-label" for="remember_me">Remember me</label>
                            </div><a class="nav-link-inline font-size-sm" href="account-password-recovery.html">Forgot password?</a>
                        </div>
                        <hr class="mt-4">
                        <div class="text-right pt-4">
                            <button class="btn btn-primary" type="submit">Sign In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 pt-5 pt-sm-3">
            <h2 class="h4 mb-3">No account? Sign up</h2>
            <p class="text-muted mb-4">Registration takes less than a minute but gives you full control over your orders.</p>
            <form class="needs-validation" novalidate="">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reg-fn">First Name</label>
                            <input class="form-control" type="text" required="" id="reg-fn">
                            <div class="invalid-feedback">Please enter your first name!</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reg-ln">Last Name</label>
                            <input class="form-control" type="text" required="" id="reg-ln">
                            <div class="invalid-feedback">Please enter your last name!</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reg-email">E-mail Address</label>
                            <input class="form-control" type="email" required="" id="reg-email">
                            <div class="invalid-feedback">Please enter valid email address!</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reg-phone">Phone Number</label>
                            <input class="form-control" type="text" required="" id="reg-phone">
                            <div class="invalid-feedback">Please enter your phone number!</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reg-password">Password</label>
                            <input class="form-control" type="password" required="" id="reg-password">
                            <div class="invalid-feedback">Please enter password!</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reg-password-confirm">Confirm Password</label>
                            <input class="form-control" type="password" required="" id="reg-password-confirm">
                            <div class="invalid-feedback">Passwords do not match!</div>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <button class="btn btn-primary" type="submit">Sign Up</button>
                </div>
            </form>
        </div>
    </div>
</div>';
    
    	
    return [
      '#markup' => $tag,
      '#allowed_tags' => ['script', 'div', 'span', 'ul', 'li', 'a', 'i', 'form', 'label', 'input', 'button', 'p', 'h2', 'h3', 'style'],
    ];
  } 
}
