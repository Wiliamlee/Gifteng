package com.venefica.connect;

import java.util.List;
import javax.inject.Inject;
import org.apache.commons.logging.Log;
import org.apache.commons.logging.LogFactory;
import org.springframework.social.connect.Connection;
import org.springframework.social.connect.ConnectionFactory;
import org.springframework.social.connect.ConnectionFactoryLocator;
import org.springframework.social.connect.UsersConnectionRepository;
import org.springframework.social.connect.support.OAuth1ConnectionFactory;
import org.springframework.social.connect.support.OAuth2ConnectionFactory;
import org.springframework.social.connect.web.SignInAdapter;
import org.springframework.social.support.URIBuilder;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.context.request.NativeWebRequest;
import org.springframework.web.servlet.view.RedirectView;

/**
 * Implements "Sign In" behavior using credentials of well-known social network
 * providers. This controller is based on
 * {@link org.springframework.social.connect.web.ProviderSignInController}.
 *
 * @author Sviatoslav Grebenchukov
 */
@Controller
@RequestMapping("/signin")
public class SignInController {

    private static final String ERROR_CODE = "error";
    
    //Sets URL of application's sign-in error page.
    private final static String SIGN_IN_ERROR_URL = "/signin/" + ERROR_CODE;
    
    private static final Log logger = LogFactory.getLog(SignInController.class);
    
    @Inject
    private ConnectionFactoryLocator connectionFactoryLocator;
    @Inject
    private UsersConnectionRepository usersConnectionRepository;
    @Inject
    private SignInAdapter signInAdapter;
    @Inject
    private ConnectSupport connectSupport;

    /**
     * Process a sign in request by commencing the process of establishing a
     * connection to the provider on behalf of the user.
     * 
     * @param providerId
     * @param request
     * @return 
     */
    @RequestMapping(value = "/{providerId}", method = RequestMethod.GET)
    public RedirectView signIn(
            @PathVariable("providerId") String providerId,
            NativeWebRequest request) {
        try {
            ConnectionFactory connectionFactory = connectionFactoryLocator.getConnectionFactory(providerId);
            return new RedirectView(connectSupport.buildOAuthUrl(connectionFactory, request, null));
        } catch (Exception e) {
            logger.error("", e);
            return redirectToSignInError(ConnectSupport.ERROR_REASON_PROVIDER);
        }
    }

    /**
     * Process the authentication callback from an OAuth 1 service provider.
     * 
     * @param providerId
     * @param request
     * @return 
     */
    @RequestMapping(value = "/{providerId}/" + ConnectSupport.CALLBACK_PATH, method = RequestMethod.GET, params = ConnectSupport.PARAM_TOKEN)
    public RedirectView oauth1Callback(
            @PathVariable("providerId") String providerId,
            NativeWebRequest request) {
        try {
            OAuth1ConnectionFactory connectionFactory = (OAuth1ConnectionFactory) connectionFactoryLocator.getConnectionFactory(providerId);
            Connection connection = connectSupport.completeConnection(connectionFactory, request);
            return handleSignIn(connection, request);
        } catch (Exception e) {
            logger.warn("Exception while handling OAuth1 callback (" + e.getMessage() + "). Redirecting to " + SIGN_IN_ERROR_URL, e);
            return redirectToSignInError(ConnectSupport.ERROR_REASON_PROVIDER);
        }
    }
    
    /**
     * Process the authentication callback from an OAuth 2 service provider.
     * Called after the user authorizes the authentication, generally done once
     * by having her of she click "Allow" in their web browser at the provider's
     * site.
     * 
     * @param providerId
     * @param code
     * @param request
     * @return 
     */
    @RequestMapping(value = "/{providerId}/" + ConnectSupport.CALLBACK_PATH, method = RequestMethod.GET, params = ConnectSupport.PARAM_CODE)
    public RedirectView oath2Callback(
            @PathVariable("providerId") String providerId,
            @RequestParam("code") String code,
            NativeWebRequest request) {
        try {
            OAuth2ConnectionFactory connectionFactory = (OAuth2ConnectionFactory) connectionFactoryLocator.getConnectionFactory(providerId);
            Connection connection = connectSupport.completeConnection(connectionFactory, request, null);
            return handleSignIn(connection, request);
        } catch (Exception e) {
            logger.warn("Exception while handling OAut2 callback (" + e.getMessage() + "). Redirecting to " + SIGN_IN_ERROR_URL, e);
            return redirectToSignInError(ConnectSupport.ERROR_REASON_PROVIDER);
        }
    }

    /**
     * Process the authentication callback when neither the oauth_token or code
     * parameter is given, likely indicating that the user denied authorization
     * with the provider.
     * 
     * @param providerId
     * @return 
     */
    @RequestMapping(value = "/{providerId}/" + ConnectSupport.CALLBACK_PATH, method = RequestMethod.GET)
    public RedirectView cancelAuthorizationCallback(@PathVariable("providerId") String providerId) {
        return redirectToSignInError(ConnectSupport.ERROR_REASON_REJECT);
    }

    /**
     * Handles sign-in errors
     */
    @RequestMapping(value = "/" + ERROR_CODE, method = RequestMethod.GET, params = "reason")
    public void error(@RequestParam("reason") String reason) {
        logger.warn("Error with reason: " + reason);
    }
    
    // internal helpers
    
    private RedirectView handleSignIn(Connection connection, NativeWebRequest request) {
        List<String> userIds = usersConnectionRepository.findUserIdsWithConnection(connection);
        if (userIds.size() == 1) {
            String userId = userIds.get(0);
            usersConnectionRepository.createConnectionRepository(userId).updateConnection(connection);
            // Generate session token and perform redirect to the "special" URL.
            String redirectUrl = signInAdapter.signIn(userId, connection, request);
            return new RedirectView(redirectUrl, true);
        } else {
            return redirectToSignInError(ConnectSupport.ERROR_REASON_MULTIPLE);
        }
    }
    
    private RedirectView redirectToSignInError(String reason) {
        return new RedirectView(URIBuilder.fromUri(SIGN_IN_ERROR_URL).queryParam("reason", reason).build().toString(), true);
    }
}
