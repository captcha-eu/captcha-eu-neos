<?php
namespace CaptchaEU\CaptchaEU\FormElements;

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Error;
use Neos\Form\Core\Model\AbstractFormElement;
use Neos\Form\Core\Runtime\FormRuntime;

class Captcha extends AbstractFormElement
{

    /**
     * @Flow\InjectConfiguration()
     * @var array
     */
    protected $settings = [];


    /**
     * Check the friendly captcha solution before submitting form.
     *
     * @param FormRuntime $formRuntime The current form runtime
     * @param mixed       $elementValue The transmitted value of the form field.
     *
     * @return void
     */

    public function onSubmit(FormRuntime $formRuntime, &$elementValue)
    {
        $properties = $this->getProperties();
        $restKey = $properties['restKey'] ? $properties['restKey'] : ($this->settings['restKey'] ? $this->settings['restKey'] : null);

        if($properties["overrideRestKey"] && !empty($properties["overrideRestKey"])) {
          $restKey = $properties["overrideRestKey"];
        }

        if (empty($restKey)) {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error('Error. Please try again later.', 17942348245));
            return;
        }

        $result = ['verified' => false, 'error' => ''];

        if (empty($_POST['captcha_at_solution'])) {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(new Error('You forgot to add the solution parameter.', 1515642243));
        } else {
                $solution = $_POST["captcha_at_solution"];
                $result["verified"] = $this->checkSolution($solution, $restKey);
        }

        if ($result['verified'] === false) {
                $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
                $processingRule->getProcessingMessages()->addError(new Error($result['error'], 1380742852));
        }
    }

    public function checkSolution($solution, $restKey) {
      $ch = curl_init("https://w19.captcha.at/validate");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $solution);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Rest-Key: ' . $restKey));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);

      $resultObject = json_decode($result);
      if ($resultObject->success) {
        return true;
      } else {
        return false;
      }
    }
}
