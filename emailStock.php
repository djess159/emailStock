<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EmailStock extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'emailStock';
        $this->tab = 'checkout';
        $this->version = '1.0.0';
        $this->author = 'Djessym Belaroussi';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('emailStock');
        $this->description = $this->l('sends email when the stock of a product changes');

        $this->confirmUninstall = $this->l('are you sure you want to uninstall this module ?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

   
    public function install()
    {
        Configuration::updateValue('EMAILSTOCK_LIVE_MODE', false);
        Configuration::updateValue('EMAILSTOCK_ACCOUNT_EMAIL', Configuration::get('PS_SHOP_EMAIL'));

        return parent::install() &&
            $this->registerHook('actionUpdateQuantity');
    }

    public function uninstall()
    {
        Configuration::deleteByName('EMAILSTOCK_LIVE_MODE');
        Configuration::deleteByName('EMAILSTOCK_ACCOUNT_EMAIL');

        return parent::uninstall();
    }

    /**
     * Configuration du formulaire
     */
    public function getContent()
    {
        /**
         * Si des valeurs ont été soumises dans le formulaire, après validation, on execute prostProcess.
         */
        if(Tools::isSubmit('submitEmailStockModule'))
        {
            $email = (string)Tools::getValue('EMAILSTOCK_ACCOUNT_EMAIL');
            if(!$email || empty($email) || !Validate::isEmail($email))
            {
                $output .= $this->displayError($this->l('Invalid email'));
            }
            else
            {
                $this->postProcess();
            }
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Créez le formulaire qui sera affiché dans la configuration du module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEmailStockModule';
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        $helper->fields_value['EMAILSTOCK_ACCOUNT_EMAIL'] = Configuration::get('EMAILSTOCK_ACCOUNT_EMAIL');

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Création de la structure du formulaire.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'EMAILSTOCK_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Valeur de l'input.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EMAILSTOCK_ACCOUNT_EMAIL' => Configuration::get("EMAILSTOCK_ACCOUNT_EMAIL"),
        );
    }

    /**
     * Sauvegarde des données.
     */
    protected function postProcess()
    {   
            Configuration::updateValue('EMAILSTOCK_ACCOUNT_EMAIL', Tools::getValue('EMAILSTOCK_ACCOUNT_EMAIL'));
        
    }

    /**
    *   Branchement sur le hook qui s'execute quand le stock est mis à jour
    */
    public function hookActionUpdateQuantity($params)
    {
        $id_product = (int) $params['id_product'];

        $context = Context::getContext();
        $quantity = (int) $params['quantity'];
        $id_product_attribute = (int) $params['id_product_attribute'];
        $id_lang = (int) $context->language->id;
        $destinataire = Configuration::get("EMAILSTOCK_ACCOUNT_EMAIL");
        $product_name = Product::getProductName($id_product,$id_product_attribute, $id_lang);

        $template_vars = array(
                '{qty}' => $quantity,
                '{product}' => $product_name,
            );

         Mail::Send($id_lang, 'product_update', 'Stock modifié' , $template_vars, $destinataire, null, null, null, null, null,dirname(__FILE__).'/mails/');

    }
}
