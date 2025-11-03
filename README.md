# ech-consultant-form-generator
### with Omnichat / Sleekflow / Kommo auto send Whatsapp msg function
A Wordpress plugin to generate a responsive consultant form for ECH company's brand websites.

## Usage
To generate a consultant form, you need to confirm active ech-lead-form-generator and ACF Pro plugin first. And you need to enter some values in dashboard setting page. Then, you may copy the below shortcode sample to start generate a consultant form. 
```
[ech_consultant]
```

## Shortcode attributes
Based on the form requirments and MSP campaigns, change the attributes or values if necessary.

Attribute | Description
----------|-------------
`tel_prefix_display` (INT) | Default prefix hidden, default is 0
`name_required` (INT) | 0 = false, 1 = true. Default is 1.
`last_name_required` (INT) | 0 = false, 1 = true. Default is 1.
`submit_label` (String) | Submit label. Default is "提交" 
`consultant_title` (String) | Consultant List Title . Default is "請選擇您專屬的顧問"
`msg_template` (String) | Insert msg template name ( create from Omnichat / Sleekflow / Kommo). If the Msg send API is SleekFlow, the msg_template should be formatted as `objectKey \| whatsappTemplateName`.
`msg_header` (String) | msg template header parameters, if template header setup image, video, document, the field is required. Eg. `image \| https://nymg.com.hk/wp-content/uploads/2024/04/NYMG.jpg`
`msg_body` (String) | msg template body parameters, if special ranking is required, the field is required. Eg. `name, booking_location, booking_item`
`msg_button` (String) | msg template button parameters, if template button setup, the field is required. Eg. `https://nymg.com.hk/epay-landing/`, `https://example.com, https://example2.com`
`fbcapi_send` (INT) | Enable or disable the FB Capi . 0 = disable, 1 = enable. Default is 0.