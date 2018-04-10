<?php

namespace AppBundle\Submission;

use AppBundle\Identity\IdentityManager;
use AppBundle\Exception\HttpFriendlyException;
use AppBundle\Factory\InputSubmissionFactory;
use AppBundle\Notifications\NotificationManager;
use AppBundle\Utility\RequestPayload;
use As3\Modlr\Models\Model;
use Symfony\Component\HttpFoundation\JsonResponse;

class SubmissionManager
{
    /**
     * @var IdentityManager
     */
    private $identityManager;

    /**
     * @var SubmissionHandlerInterface
     */
    private $handlers = [];

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * @var InputSubmissionFactory
     */
    private $submissionFactory;

    /**
     * @param   InputSubmissionFactory  $submissionFactory
     * @param   IdentityManager         $identityManager
     * @param   NotificationManager     $notificationManager
     */
    public function __construct(InputSubmissionFactory $submissionFactory, IdentityManager $identityManager, NotificationManager $notificationManager)
    {
var_dump(__method__);
        $this->submissionFactory   = $submissionFactory;
        $this->identityManager     = $identityManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param   SubmissionHandlerInterface    $handler
     * @return  self
     */
    public function addHandler(SubmissionHandlerInterface $handler)
    {
        $this->handlers[$handler->getSourceKey()] = $handler;
        return $this;
    }

    /**
     * Handles a submission for the provided source key and payload.
     *
     * @param   string          $sourceKey
     * @param   RequestPayload  $payload
     * @return  JsonResponse
     * @throws  HttpFriendlyException
     */
    public function processFor($sourceKey, RequestPayload $payload)
    {
var_dump(__method__);
//var_dump(__method__.' - ' . $sourceKey);

var_dump(__method__.' - 1 - start processFor method - check if handler exists and do validateAlways for this sourceKey: '.$sourceKey);

        if (!isset($this->handlers[$sourceKey])) {
            throw new HttpFriendlyException(sprintf('No submission handler found for "%s"', $sourceKey), 404);
        }

        // Send the validate always hook.
        $this->callHookFor($sourceKey, 'validateAlways', [$payload]);

var_dump(__method__.' - 2 - get active identity, and process loggedIn/loggedOut validation method/rules based it');

        // Send the appropriate identity state validation hook.
        $activeIdentity = $this->identityManager->getActiveIdentity();
        if (null !== $activeIdentity && 'identity-account' === $activeIdentity->getType()) {
            $this->callHookFor($sourceKey, 'validateWhenLoggedIn', [$payload, $activeIdentity]);
        } else {
            $this->callHookFor($sourceKey, 'validateWhenLoggedOut', [$payload, $activeIdentity]);
        }

var_dump(__method__.' - 3 - create the submission!');

        // Create the submission.
        $submission = $this->createSubmission($sourceKey, $payload);
var_dump(__method__. ' - submission created: '.$submission->getId());
//var_dump($submission);


var_dump(__method__.' - 4 - call hook to create the Identity for the payload');
//var_dump($payload);

        $identity = $this->callHookFor($sourceKey, 'createIdentityFor', [$payload]);
        if (!$identity instanceof Model) {
var_dump(__method__.' - 4.1 - Not IdentityAccountHandler so dont use createIdentity for (reg/password only users?) - do the dance');
            // The submission did not handle its own identification.
            // Do the native identity/submission "dance."
            $identity = $this->determineIdentity($submission, $payload);
var_dump(__method__.' - 4.2 - determineIdentity done, should have identity figured out now: '.$identity->getId());
        }

var_dump(__method__.' - 5 - if identity is NOT null now, then set it on the submission');

        if (null !== $identity) {
var_dump(__method__.' - 5.1 - identity is not null - so adding it to the submission');
            $identityFactory = $this->identityManager->getidentityFactoryForModel($identity);
            $submission->set('identity', $identity);
var_dump(__method__.' - 5.2 - identity set onto the subission');
        }

var_dump(__method__.' - 6 - call hook for beforeSave for: '.$sourceKey);

        // Send the before save hook to allow the handler to perform additional logic.
        $this->callHookFor($sourceKey, 'beforeSave', [$payload, $submission]);

var_dump(__method__.' - 7 - check if cant save identity or submission - throw error if you cant');

        // Throw error if unable to save the identity or the submission.
        if (null !== $identity && true !== $result = $identityFactory->canSave($identity)) {
var_dump(__method__.' - 7.1 - cannot save identity throw error');
            $result->throwException();
        }
        if (true !== $result = $this->submissionFactory->canSave($submission)) {
var_dump(__method__.' - 7.2 - cannot save submission, throw error');
            $result->throwException();
        }

var_dump(__method__.' - 8 - call canSave hook for: '.$sourceKey);

        // Send the can save hook to allow for additional save checks.
        $this->callHookFor($sourceKey, 'canSave', []);

var_dump(__method__.' - 9 - check if identity is null - save if not');

        // Save the identity and submission
        if (null !== $identity) {
var_dump(__method__.' - 9.1 - identity not null - do identityFactory->save');
var_dump('identity ID: '.$identity->getId());
            $identityFactory->save($identity);
var_dump(__method__.' - 9.2 - identity saved');
        }
var_dump(__method__.' - 9.3 - done saving identity');
        $this->submissionFactory->save($submission);

var_dump(__method__.' - 10 - call save gook for: '.$sourceKey);

        // Send the save hook for additional saving.
        $this->callHookFor($sourceKey, 'save', []);

var_dump(__method__.' - 11 - set the active identity now if its not null');

        // Set the active identity, if applicable.
        if (null !== $identity) {
            $this->identityManager->setActiveIdentity($identity);
        }

var_dump(__method__.' - 12 - send the email notifications forthe submission');

        // Send email notifications.
        $this->notificationManager->sendNotificationFor($submission);
        $this->notificationManager->notifySubmission($submission, $payload->getNotify());

var_dump(__method__.' - 13 - emails sent, finally returns the results of the hook call to createResponse for: '.$sourceKey);

        // Return the response.
        return $this->callHookFor($sourceKey, 'createResponseFor', [$submission]);
    }

    /**
     * Calls a handler hook method.
     *
     * @param   string  $sourceKey
     * @param   string  $method
     * @param   array   $args
     */
    private function callHookFor($sourceKey, $method, array $args)
    {
var_dump(__method__." - ".$method);
//var_dump($method);

        if (isset($this->handlers[$sourceKey])) {
var_dump(__method__ . ' - handler for sourceKey ('.$sourceKey.') is set');
            $handler = $this->handlers[$sourceKey];
            if ('createIdentityFor' === $method && !$handler instanceof IdentifiableSubmissionHandlerInterface) {
var_dump(__method__.' - ***** ignoring hook call for this sourceKey:method -- '.$sourceKey.":".$method. ' - is createIdentityFor AND handler is not an inscne of IdentifiableSubmissionHandlerInterace');                
                return;
            }
            return call_user_func_array([$handler, $method], $args);
        } else {
var_dump(__method__.' - ***** BAAAD NO handler for this thing');
        }
    }

    /**
     * Creates a submission model for the provided source key.
     *
     * @param   string          $sourceKey
     * @param   RequestPayload  $payload
     * @return  Model
     */
    private function createSubmission($sourceKey, RequestPayload $payload)
    {
var_dump(__method__);
//var_dump($sourceKey);
//var_dump($payload);
        $submission = $this->submissionFactory->create($payload);
        $submission->set('sourceKey', $sourceKey);
        return $submission;
    }

    /**
     * Determines the identity to use for the submission.
     * Will use an identity if an account is not logged in.
     * If no identity is found, it will create one.
     *
     * @todo    Will need to determine how to get the identity if an email isn't provided with the submission.
     * @param   Model           $submission
     * @param   RequestPayload  $payload
     * @return  Model|null      The appropriate identity for the submission.
     */
    private function determineIdentity(Model $submission, RequestPayload $payload)
    {
var_dump(__method__);
var_dump(__method__.' - get active account from identity');
        if (null !== $account = $this->identityManager->getActiveAccount()) {
var_dump(__method__.' - logged in, remove primary email and emails from identity and apply the rest to their existing account');
var_dump($account);
            // Logged in account.
            // Make sure email isn't updated by this form. @todo Will need to determine a better way of handling this.
            $payload->getIdentity()->remove('primaryEmail');
            $payload->getIdentity()->remove('emails');
            // Update account data with the submission data.
            $factory = $this->identityManager->getIdentityFactoryForModel($account);
            $factory->apply($account, $payload->getIdentity()->all());
var_dump(__method__.' - return account');
            return $account;
        }

var_dump(__method__.' - not logged in, get primaryEmail from identity and do upsertIdentiesFor its matches');
        // Account is not logged in. Create/update the identity, if possible.
        $emailAddress = $payload->getIdentity()->get('primaryEmail');
var_dump(__method__.' - primaryEmail: '.$payload->getIdentity()->get('primaryEmail'));

// @jpdev pass request in here?
//var_dump('%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%');
//var_dump($this->submissionFactory->requestStack);

        $identities   = $this->identityManager->upsertIdentitiesFor($emailAddress, $payload->getIdentity()->all());
        if (empty($identities)) {
var_dump(__method__.' - no identities found - return null');
            return;
        }
var_dump(__method__.' - return the first one found: '.$identities[0]->getId());
//var_dump($identities[0]->getId());
        return $identities[0];
    }
}
