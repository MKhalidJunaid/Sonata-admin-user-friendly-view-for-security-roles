Soanta Admin Ehanced View For Security Roles
--

In Sonata admin if you wish to change display security roles as a user friendly view you have to override below sonata's services 

 - sonata.user.editable_role_builder
 - sonata.user.form.type.security_roles

And definitions will look like as below


        <services>
            <service id="sonata.user.editable_role_builder" class="Acme\DemoBundle\Security\EditableRolesBuilder">
                <argument type="service" id="security.context" />
                <argument type="service" id="sonata.admin.pool" />
                <argument>%security.role_hierarchy.roles%</argument>
            </service>
            <service id="sonata.user.form.type.security_roles" class="Acme\DemoBundle\Form\Type\SecurityRolesType">
                <tag name="form.type" alias="sonata_security_roles" />
                <argument type="service" id="sonata.user.editable_role_builder" />
            </service>

        </services>

And define your classes in these services for demo code i have user `Acme\DemoBundle`

Now `SecurityRolesType` class is dependendant of Sonata's `EditableRolesBuilder` you have to make it depandent of your `EditableRolesBuilder` Class so in same way override dependency of sonata's `RestoreRolesTransformer` to your class

I have transformed all roles to an array of module wise roles in `SecurityRolesType.php` and passed it to view all customization you can view in this file 

Also override the twig template for roles you can overrride it by coping `form_admin_fields.html.twig` from `vendor\sonata-project\user-bundle\Resources\views` and adding `app\Resources\SonataUserBundle\views\Form` path it will override parent twig file,In twig file i have tried to use accordion control bootstrap to display roles module wise and with apropirate permissions

**Note: This repository will only display permissions [Create,Edit,View,List,Export,Delete,Master] it will not handle custom permission**

Last step import your service file in main configuration file that is `config.yml`

        imports:
            - { resource: parameters.yml }
            - { resource: security.yml }
            - { resource: @AcmeDemoBundle/Resources/config/admin.xml }