<?php
/**
 * Privacy policy template.
 *
 * @package FP\Privacy
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

$org      = isset( $options['org_name'] ) ? $options['org_name'] : '';
$address  = isset( $options['address'] ) ? $options['address'] : '';
$dpo_name = isset( $options['dpo_name'] ) ? $options['dpo_name'] : '';
$dpo_mail = isset( $options['dpo_email'] ) ? $options['dpo_email'] : '';
$privacy_mail = isset( $options['privacy_email'] ) ? $options['privacy_email'] : '';
$vat      = isset( $options['vat'] ) ? $options['vat'] : '';
$generated_at    = isset( $generated_at ) ? (int) $generated_at : 0;
$categories_meta = isset( $categories_meta ) && is_array( $categories_meta ) ? $categories_meta : array();

$date_format  = (string) get_option( 'date_format' );
$time_format  = (string) get_option( 'time_format' );
$display_date = trim( $date_format . ' ' . $time_format );

if ( '' === $display_date ) {
    $display_date = 'F j, Y';
}

$last_generated = $generated_at > 0 ? wp_date( $display_date, $generated_at ) : wp_date( $display_date );

if ( ! function_exists( 'fp_privacy_format_service_cookies' ) ) {
    /**
     * Format service cookies into readable strings.
     *
     * @param mixed $cookies Raw cookies array.
     *
     * @return array<int, string>
     */
    function fp_privacy_format_service_cookies( $cookies ) {
        if ( ! is_array( $cookies ) ) {
            return array();
        }

        $formatted = array();

        foreach ( $cookies as $cookie ) {
            if ( ! is_array( $cookie ) ) {
                continue;
            }

            $name        = isset( $cookie['name'] ) ? (string) $cookie['name'] : '';
            $domain      = isset( $cookie['domain'] ) ? (string) $cookie['domain'] : '';
            $duration    = isset( $cookie['duration'] ) ? (string) $cookie['duration'] : '';
            $description = isset( $cookie['description'] ) ? (string) $cookie['description'] : '';

            $details = array();

            if ( '' !== $domain ) {
                $details[] = sprintf( /* translators: %s: cookie domain. */ __( 'Domain: %s', 'fp-privacy' ), $domain );
            }

            if ( '' !== $duration ) {
                $details[] = sprintf( /* translators: %s: cookie duration. */ __( 'Duration: %s', 'fp-privacy' ), $duration );
            }

            if ( '' !== $description ) {
                $details[] = $description;
            }

            if ( '' === $name && empty( $details ) ) {
                continue;
            }

            $label = '' !== $name ? $name : __( 'Unnamed cookie', 'fp-privacy' );

            if ( $details ) {
                $label .= ' (' . implode( ' — ', $details ) . ')';
            }

            $formatted[] = $label;
        }

        return $formatted;
    }
}

if ( ! function_exists( 'fp_privacy_get_service_value' ) ) {
    /**
     * Safely fetch a scalar service value.
     *
     * @param mixed  $service Service payload.
     * @param string $key     Array key to retrieve.
     *
     * @return string
     */
    function fp_privacy_get_service_value( $service, $key ) {
        if ( ! is_array( $service ) || ! isset( $service[ $key ] ) ) {
            return '';
        }

        $value = $service[ $key ];

        if ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
            return (string) $value;
        }

        return '';
    }
}
?>
<section class="fp-privacy-policy">
<h2><?php echo esc_html__( 'Overview', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'This privacy policy explains how we collect, use, store, share and protect personal data when you visit or interact with our website and services. We are committed to processing personal data in full compliance with Regulation (EU) 2016/679 (General Data Protection Regulation, "GDPR"), the ePrivacy Directive 2002/58/EC as amended and implemented in EU Member States, applicable national privacy laws, and relevant guidance issued by the European Data Protection Board (EDPB) and national supervisory authorities. This policy reflects the state of EU and EEA privacy guidance as of October 2025 and incorporates recent case law from the Court of Justice of the European Union (CJEU). We are transparent about our data processing activities and provide you with meaningful control over your personal information.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Definitions', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'For the purposes of this policy: "Personal data" means any information relating to an identified or identifiable natural person (data subject); an identifiable natural person is one who can be identified, directly or indirectly, in particular by reference to an identifier such as a name, identification number, location data, online identifier or to one or more factors specific to the physical, physiological, genetic, mental, economic, cultural or social identity of that natural person. "Processing" covers any operation or set of operations performed on personal data or sets of personal data, whether or not by automated means, such as collection, recording, organisation, structuring, storage, adaptation or alteration, retrieval, consultation, use, disclosure by transmission, dissemination or otherwise making available, alignment or combination, restriction, erasure or destruction. "Controller" means the natural or legal person which determines the purposes and means of the processing of personal data. "Processor" means a natural or legal person which processes personal data on behalf of the controller. "Services" refers to our website, applications, features, content and related services we offer or make available to you.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Data controller', 'fp-privacy' ); ?></h2>
<p>
<?php echo esc_html( $org ); ?>
<?php if ( $vat ) : ?> — <?php echo esc_html( sprintf( __( 'VAT/Tax ID: %s', 'fp-privacy' ), $vat ) ); ?><?php endif; ?><br/>
<?php echo esc_html( $address ); ?><br/>
<?php if ( $privacy_mail ) : ?><?php echo esc_html( sprintf( __( 'Contact: %s', 'fp-privacy' ), $privacy_mail ) ); ?><?php endif; ?>
</p>

<h2><?php echo esc_html__( 'Applicable regulations', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'All processing activities are carried out in strict accordance with the GDPR (Regulation (EU) 2016/679), the ePrivacy Directive (Directive 2002/58/EC) as implemented in national legislation of EU Member States, consumer protection rules under Regulation (EU) 2016/679, Directive 2005/29/EC on unfair commercial practices, and any sector-specific obligations that apply to our services. We also comply with national data protection laws implementing these European frameworks, including provisions on electronic communications privacy, direct marketing, cookies and similar tracking technologies. Our processing activities respect the fundamental rights and freedoms of data subjects as guaranteed by the Charter of Fundamental Rights of the European Union, particularly Article 7 (respect for private and family life) and Article 8 (protection of personal data). We follow recommendations and guidelines issued by the European Data Protection Board, the Article 29 Working Party and relevant national supervisory authorities.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Categories of data we process', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Depending on how you interact with our website and services, we may process various categories of personal data including: identification and contact data (such as full name, email address, phone number, postal address, username); account and authentication data (such as passwords, security questions, authentication tokens); technical data (such as IP address, browser type and version, operating system, device identifiers, unique device tokens, advertising identifiers, connection information, access logs, referral URLs); usage and behavioural data (such as pages visited, features used, actions taken, click patterns, mouse movements, scroll depth, time spent on pages, search queries, interaction timestamps); geolocation data (such as precise GPS location when permission is granted, or approximate location derived from IP address); preference and settings data (such as cookie consent choices, marketing preferences, language preferences, accessibility settings, notification preferences); transactional and commercial data (such as purchase history, payment details, billing information, order records); communications data (such as correspondence with customer support, feedback, survey responses, chat transcripts); and profile and derived data (such as inferred interests, preferences, demographic characteristics based on your interactions). Additional categories of information collected by specific third-party services integrated into our website are described in detail in the services and cookies table below, along with their respective purposes and legal bases.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Source of the data', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We collect personal data from various sources: (1) Data you provide directly: when you register for an account, complete forms, subscribe to newsletters, make purchases, request information, participate in surveys, communicate with customer support, post content, participate in community features, or otherwise voluntarily submit information through our services. (2) Data we collect automatically: when you visit or use our services, we automatically collect certain technical and usage data through cookies, web beacons, pixels, local storage, server logs and similar tracking technologies. This includes information about your device, browser, IP address, pages viewed, features used, timestamps, referral sources and navigation patterns. See our Cookie Policy for detailed information about cookies and similar technologies. (3) Data from third parties: we may receive personal data from trusted business partners, service providers, analytics providers, advertising networks, social media platforms (when you connect your account or interact with social features), payment processors, fraud prevention services, data enrichment providers, and publicly accessible sources (such as public registers, directories, social media profiles set to public) when legally permitted and necessary for legitimate business purposes. (4) Data from combined sources: we may combine data collected from different sources to create a more complete picture of our users, improve our services, personalise experiences and enhance security, always in compliance with applicable data protection requirements.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Mandatory and optional data', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Whenever personal data is requested through forms or other interfaces, we clearly distinguish and indicate which data fields are mandatory (required) to provide the requested service and which are optional (voluntary). Mandatory fields are typically marked with an asterisk (*) or another clear visual indicator, and we explain why the information is necessary. Refusing to share optional data will not have negative consequences on your ability to use our services, receive assistance or exercise your rights. However, failing to provide mandatory data may prevent us from fulfilling your request, completing a transaction, creating an account, responding to your inquiry or providing certain features or services. In such cases, we will explain the consequences of not providing the required information. The distinction between mandatory and optional data is based on the necessity and proportionality principles under GDPR Article 5(1)(c), ensuring we only collect data that is adequate, relevant and limited to what is necessary for the specified purposes.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Purposes of processing', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We process personal data for the following specific purposes: (1) Service delivery and performance: to provide, operate, maintain and deliver our services, features and functionalities; to create and manage user accounts; to process transactions and fulfill orders; to provide customer support and respond to inquiries and requests; to send service-related communications, notifications and updates. (2) Security and fraud prevention: to detect, prevent and respond to security incidents, fraud, abuse, illegal activities and violations of our terms of service; to verify identity and authenticate users; to protect the rights, property and safety of our organization, users and the public. (3) Analytics and performance measurement: to analyze usage patterns, measure effectiveness, understand user behavior, monitor service performance, identify technical issues and generate statistical insights; to conduct research and development. (4) Service improvement and innovation: to improve existing features, develop new features and services, enhance user experience, test new functionalities and optimize our content, design and offerings. (5) Personalization and tailored experiences: to deliver personalized content, recommendations, advertisements and experiences tailored to your interests and preferences, only where you have granted the relevant consent or where permitted under applicable law. (6) Marketing and communications: to send promotional communications, newsletters, marketing materials and information about products and services that may interest you, only with your prior consent where required by law. (7) Legal compliance and obligations: to comply with legal obligations, regulatory requirements, court orders, government requests and to enforce our legal rights and agreements. (8) Business operations: to manage our business operations, maintain records, conduct internal administration, perform accounting and auditing functions. Each processing purpose is associated with a specific legal basis as detailed in the Legal Bases section below.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Legal bases', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Each processing activity is grounded on one or more of the following legal bases under Article 6(1) GDPR: (a) Consent (Article 6.1.a GDPR): for optional processing activities such as non-essential analytics, marketing cookies, profiling for marketing purposes, third-party advertising, optional service features and marketing communications. Consent must be freely given, specific, informed and unambiguous, provided through a clear affirmative action. You can withdraw consent at any time without affecting the lawfulness of processing based on consent before its withdrawal. (b) Contractual necessity (Article 6.1.b GDPR): when processing is necessary for the performance of a contract to which you are party (such as our Terms of Service) or to take steps at your request prior to entering into a contract. This includes processing necessary to provide requested services, create and manage accounts, process payments and deliver purchased products or services. (c) Compliance with legal obligations (Article 6.1.c GDPR): when processing is necessary to comply with legal obligations to which we are subject, such as tax and accounting requirements, regulatory compliance, responses to lawful government requests, law enforcement cooperation and mandatory record-keeping obligations. (d) Legitimate interests (Article 6.1.f GDPR): when processing is necessary for purposes of our or a third party\'s legitimate interests, except where such interests are overridden by your interests or fundamental rights and freedoms. We rely on legitimate interests for: security and fraud prevention; essential analytics to understand service performance and technical issues; network and information security; direct marketing to existing customers for similar products; business continuity and disaster recovery; exercising or defending legal claims. Before relying on legitimate interest, we conduct and document a balancing test (Legitimate Interest Assessment) that weighs our interests against your rights, considers the nature and sensitivity of data, implements appropriate safeguards and follows the latest guidance from the European Data Protection Board and national supervisory authorities. (e) Vital interests (Article 6.1.d GDPR): in rare cases, when processing is necessary to protect the vital interests of data subjects or another natural person. (f) Public interest or official authority (Article 6.1.e GDPR): when applicable for processing carried out in the public interest or in the exercise of official authority. The specific legal basis for each processing purpose and service is indicated in the services and cookies table below.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Recipients and data transfers', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Personal data may be disclosed to or shared with the following categories of recipients, strictly limited to what is necessary for the purposes indicated: (1) Service providers and processors: third-party vendors, contractors and service providers who process data on our behalf under written data processing agreements (hosting providers, cloud storage, CDN services, email delivery, payment processors, analytics providers, customer support platforms, security services). (2) Business partners: trusted partners with whom we collaborate to deliver services, fulfill orders or provide integrated functionalities, subject to contractual confidentiality and data protection obligations. (3) Advertising and marketing partners: when you have provided consent, we may share data with advertising networks, marketing platforms and social media services for targeted advertising and marketing purposes. (4) Professional advisors: lawyers, accountants, auditors, insurers and other professional advisors when necessary for business operations or legal compliance. (5) Competent authorities: law enforcement, regulatory bodies, courts, government agencies and other public authorities when required by law, in response to legal process, to protect rights and safety, or to comply with regulatory obligations. (6) Corporate transactions: in connection with any merger, sale, acquisition, restructuring or transfer of assets, potential buyers or investors may receive personal data subject to confidentiality obligations. (7) With your consent: other third parties when you have provided specific consent or at your direction. When recipients are established outside the European Economic Area (EEA), international data transfers occur only where: (i) the European Commission has issued an adequacy decision recognizing the destination country provides an adequate level of protection (Article 45 GDPR); or (ii) appropriate safeguards are in place, such as Standard Contractual Clauses approved by the European Commission (Article 46 GDPR), Binding Corporate Rules, approved codes of conduct or certification mechanisms; and (iii) additional technical, organizational and contractual measures are implemented consistent with the latest recommendations of the European Data Protection Board (particularly following the Schrems II decision of the Court of Justice of the European Union) to ensure an essentially equivalent level of protection. We assess the legal regime of destination countries and implement supplementary measures where necessary. Details of specific international transfers and safeguards are available upon request.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Processors and authorised personnel', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Access to personal data is strictly controlled and limited on a need-to-know basis. Only authorized personnel who have been properly trained on data protection principles, confidentiality obligations, security procedures and relevant policies are granted access to personal data. All employees, contractors and other personnel with access to personal data are bound by contractual confidentiality obligations and are subject to disciplinary action for breaches. We implement role-based access controls, authentication mechanisms, activity logging and regular access reviews. External service providers, vendors and other third parties that process personal data on our behalf ("processors") operate under written data processing agreements (also known as Data Processing Agreements or DPAs) that fully reflect Article 28 GDPR requirements. These agreements require processors to: process data only on documented instructions; implement appropriate technical and organizational security measures; maintain confidentiality; assist with data subject rights requests; assist with security incidents and data breach notifications; delete or return personal data at the end of the relationship; demonstrate compliance through audits and inspections; engage sub-processors only with prior authorization and under equivalent contractual obligations. We conduct due diligence assessments of processors before engagement, regularly monitor and assess their compliance with contractual obligations, review audit reports and security certifications, and maintain an updated register of processors and processing activities as required by Article 30 GDPR.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Security measures', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We implement appropriate technical and organizational measures to ensure a level of security appropriate to the risk, in accordance with Article 32 GDPR, taking into account the state of the art, implementation costs, the nature, scope, context and purposes of processing, and the risks to the rights and freedoms of individuals. Our security measures include: (1) Technical measures: encryption of personal data in transit using TLS/SSL protocols and at rest using industry-standard encryption algorithms; secure authentication mechanisms including strong password policies, multi-factor authentication and session management; access controls with role-based permissions, least privilege principle and regular access reviews; network security including firewalls, intrusion detection and prevention systems, network segmentation and security monitoring; logging and monitoring of access, activities, security events and anomalies; regular vulnerability assessments, penetration testing and security audits; secure software development practices, code reviews and security testing; data backup and recovery procedures to ensure availability and resilience; pseudonymization and anonymization where appropriate to reduce risks. (2) Organizational measures: data protection policies, procedures and guidelines; data minimization and storage limitation principles applied throughout data lifecycle; staff training and awareness programs on data protection, security and confidentiality; incident response and data breach management procedures; regular compliance assessments and internal audits; vendor management and third-party security assessments; business continuity and disaster recovery planning; privacy by design and privacy by default principles integrated into systems and processes; documented data protection impact assessments (DPIAs) for high-risk processing activities. (3) Physical measures: physical access controls to facilities and server rooms; environmental controls and monitoring; secure disposal of media and equipment. We regularly test, assess and evaluate the effectiveness of these technical and organizational measures, update them to address evolving threats and maintain alignment with industry best practices and regulatory guidance.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Automated decision-making and profiling', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We may use profiling, which means any form of automated processing of personal data to evaluate certain personal aspects, particularly to analyze or predict aspects concerning your preferences, interests, behavior, location or movements. Profiling activities may include: analyzing your usage patterns to recommend content or products; segmenting users for marketing purposes; personalizing website content and user interfaces; predicting interests based on browsing history and interactions. Profiling is only conducted when based on a valid legal basis (typically your consent or our legitimate interests after balancing test) and with appropriate safeguards. Automated decision-making refers to making decisions solely by automated means without any human involvement. We do not engage in automated decision-making that produces legal effects concerning you or similarly significantly affects you (as defined in Article 22 GDPR) unless: (i) it is necessary for entering into or performing a contract between you and us; (ii) it is authorized by Union or Member State law to which we are subject and which provides suitable measures to safeguard your rights, freedoms and legitimate interests; or (iii) it is based on your explicit consent. In cases where automated decision-making is used, we implement appropriate safeguards including: providing meaningful information about the logic involved; ensuring human intervention is available; allowing you to express your point of view and contest the decision; conducting regular accuracy and bias assessments. You have the right not to be subject to decisions based solely on automated processing, including profiling, which produce legal effects or similarly significantly affect you, and you can exercise this right by contacting us as indicated in the "How to exercise your rights" section.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Retention', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Personal data are retained only for as long as necessary to fulfill the purposes for which they were collected, in accordance with the storage limitation principle under Article 5(1)(e) GDPR. Our retention periods are based on: (1) Purpose-based retention: data is retained as long as necessary to provide services, maintain accounts, fulfill contractual obligations and achieve the purposes described in this policy. (2) Legal and regulatory retention requirements: certain data must be retained for specific periods to comply with legal obligations such as tax laws (typically 7-10 years for financial records), accounting requirements, regulatory obligations, employment laws and other statutory retention duties. (3) Legal claims and litigation: data may be retained longer when necessary to establish, exercise or defend legal claims, typically until the expiration of applicable statutes of limitation. (4) Consent-based retention: when processing is based on consent, data is retained until consent is withdrawn, unless another legal basis applies or legal retention obligations require continued storage. (5) Legitimate interest retention: when based on legitimate interests, data is retained for as long as the legitimate interest persists and is not overridden by your rights. Specific retention periods include: account data is retained while your account is active and for a limited period after closure; transactional records are retained according to applicable financial and tax regulations; marketing consent records are stored for the period required by law to demonstrate compliance (typically 3-5 years after withdrawal); consent management records (cookie consents) are retained as required by applicable law and guidance (typically 6-24 months); access logs and security data are typically retained for 6-12 months unless longer retention is required for security investigations; cookies and similar technologies follow the lifespan indicated in the cookie tables and our Cookie Policy. At the end of applicable retention periods, personal data is securely deleted, destroyed or anonymized (rendered non-identifiable) such that it can no longer be attributed to an identifiable individual. We conduct periodic reviews of stored data to ensure compliance with retention policies and deletion of data that is no longer necessary. Detailed retention schedules for specific data categories and processing activities are available upon request.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Data subject rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Under the GDPR and applicable data protection laws, you have the following rights regarding your personal data: (1) Right of access (Article 15 GDPR): You have the right to obtain confirmation as to whether personal data concerning you is being processed and, where that is the case, access to the personal data and information about the processing including purposes, categories of data, recipients, retention periods and sources of the data. (2) Right to rectification (Article 16 GDPR): You have the right to obtain without undue delay the rectification of inaccurate personal data and to have incomplete personal data completed. (3) Right to erasure / right to be forgotten (Article 17 GDPR): You have the right to obtain the erasure of personal data concerning you without undue delay when: the data is no longer necessary for the purposes; you withdraw consent and there is no other legal basis; you object to processing based on legitimate interests and there are no overriding legitimate grounds; the data has been unlawfully processed; erasure is required for compliance with a legal obligation; or the data was collected in relation to the offer of information society services to children. This right does not apply when processing is necessary for compliance with legal obligations, for the establishment, exercise or defense of legal claims, or other exceptions under Article 17(3). (4) Right to restriction of processing (Article 18 GDPR): You have the right to obtain restriction of processing when: you contest the accuracy of data (for the period of verification); processing is unlawful and you oppose erasure and request restriction instead; we no longer need the data but you require it for legal claims; or you have objected to processing pending verification of whether our legitimate grounds override yours. (5) Right to data portability (Article 20 GDPR): You have the right to receive personal data concerning you which you have provided to us in a structured, commonly used and machine-readable format, and to transmit that data to another controller, where processing is based on consent or contract and is carried out by automated means. (6) Right to object (Article 21 GDPR): You have the right to object at any time to processing of your personal data based on legitimate interests or for the performance of a task in the public interest, on grounds relating to your particular situation. We will cease processing unless we demonstrate compelling legitimate grounds that override your interests, rights and freedoms, or for the establishment, exercise or defense of legal claims. You have an absolute right to object to processing for direct marketing purposes, including profiling related to direct marketing. (7) Right to withdraw consent (Article 7(3) GDPR): When processing is based on consent, you have the right to withdraw your consent at any time without affecting the lawfulness of processing based on consent before its withdrawal. (8) Right not to be subject to automated decision-making (Article 22 GDPR): You have the right not to be subject to decisions based solely on automated processing, including profiling, which produce legal effects concerning you or similarly significantly affect you, subject to certain exceptions. (9) Right to lodge a complaint (Article 77 GDPR): You have the right to lodge a complaint with a supervisory authority, in particular in the Member State of your habitual residence, place of work or place of the alleged infringement.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'How to exercise your rights', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'To exercise any of your data protection rights, please submit your request by: (1) sending an email to the contact address indicated in the Data Controller section above; (2) using the dedicated contact form available on our website; (3) sending written correspondence to the postal address indicated in the Data Controller section; or (4) contacting our Data Protection Officer if designated. Your request should clearly identify which right(s) you wish to exercise and provide sufficient information to enable us to identify you and locate your personal data. We will respond to your request without undue delay and in any event within one month of receipt, in accordance with Articles 12 and 15-22 GDPR. This period may be extended by two further months where necessary, taking into account the complexity of the request and the number of requests. If we extend the response period, we will inform you of the extension and the reasons for delay within one month of receipt of your request. To ensure security and protect against fraudulent requests, we may request additional information to verify your identity before responding to your request, particularly for access, erasure or portability requests. This is a security measure to ensure personal data is not disclosed to unauthorized persons. If we have reasonable doubts concerning your identity, we may request additional information necessary to confirm your identity. We provide information and respond to requests free of charge. However, if requests are manifestly unfounded or excessive, particularly because of their repetitive character, we may: (i) charge a reasonable fee taking into account the administrative costs of providing the information or taking the action requested; or (ii) refuse to act on the request. In such cases, we will demonstrate the manifestly unfounded or excessive character of the request. If we do not take action on your request, we will inform you without delay and at the latest within one month of receipt of the request, of the reasons for not taking action and of the possibility of lodging a complaint with a supervisory authority and seeking a judicial remedy.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Withdrawal of consent and cookie management', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'You have the right to withdraw your consent at any time without affecting the lawfulness of processing based on consent before its withdrawal. You can adjust and manage your consent choices and preferences at any time through the following methods: (1) Cookie preferences interface: access the cookie consent management tool displayed on our website footer or accessible through a dedicated preferences button. This tool allows you to review, enable or disable different categories of cookies and modify your consent choices. (2) Browser settings: you can configure your web browser to refuse all or some cookies, alert you when cookies are being set, or delete cookies that have already been set. Please note that disabling cookies may affect the functionality of our website and limit your ability to use certain features. Instructions for managing cookies in popular browsers are available in their respective help sections. (3) Opt-out mechanisms: for specific services, analytics or advertising partners, you may use their opt-out mechanisms, links to which are provided in the services and cookies table below and in our Cookie Policy. (4) Email preferences: you can unsubscribe from marketing emails by clicking the unsubscribe link included in each marketing communication or by adjusting your email preferences in your account settings. (5) Account settings: if you have an account, you can manage communication preferences, privacy settings and data sharing options in your account settings. Please note that withdrawing consent or opting out of certain processing does not affect: processing that is necessary to perform a contract with you (such as providing the core services you have requested); processing based on legal obligations; processing based on legitimate interests (though you may have the right to object); and the lawfulness of processing that occurred before you withdrew consent. Even if you opt out of marketing communications, we may still send you service-related, transactional and administrative messages necessary for your use of the services. Revoking consent for essential cookies necessary to operate the service may result in limited functionality or unavailability of certain features.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Children\'s data', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Our services are not intended for, directed at or designed to attract children under the age required by applicable law to provide valid consent for the processing of personal data. In the European Union, this age is generally 16 years, though Member States may provide for a lower age by law (not below 13 years). We do not knowingly collect, use or disclose personal data from children under the applicable age of consent without verifiable parental or guardian consent. If processing of children\'s personal data is necessary for the provision of information society services, it is lawful only where consent is given or authorized by the holder of parental responsibility over the child, and we have made reasonable efforts to verify that consent is given or authorized by the holder of parental responsibility, taking into consideration available technology. If we become aware that we have inadvertently collected personal data from a child under the applicable age without appropriate authorization, valid parental consent or another lawful basis, we will take immediate steps to: (i) delete the information as soon as possible; (ii) not use or disclose the information for any purpose; (iii) cease any profiling or tracking activities; (iv) investigate how the data was collected and take measures to prevent recurrence; and (v) take any additional steps necessary to comply with applicable laws and guidance from supervisory authorities. We encourage parents and guardians to monitor their children\'s online activities and to help enforce this policy by instructing children never to provide personal information through our services without permission. If you have reason to believe that a child under the applicable age has provided personal data to us, please contact us immediately using the contact details provided in this policy, and we will take appropriate action.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Data breach management', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We maintain comprehensive procedures and response plans to detect, assess, report, investigate, respond to and mitigate personal data breaches in compliance with Articles 33 and 34 GDPR. A personal data breach means a breach of security leading to the accidental or unlawful destruction, loss, alteration, unauthorized disclosure of, or access to, personal data transmitted, stored or otherwise processed. Our data breach management procedures include: (1) Detection and assessment: monitoring systems and security controls to detect potential breaches; procedures for employees, contractors and processors to report suspected breaches; rapid assessment of the nature, scope and potential consequences of the breach; determination of whether the breach is likely to result in a risk or high risk to the rights and freedoms of individuals. (2) Notification to supervisory authority (Article 33 GDPR): when a personal data breach is likely to result in a risk to the rights and freedoms of individuals, we notify the competent supervisory authority without undue delay and, where feasible, not later than 72 hours after having become aware of the breach. If notification is not made within 72 hours, we provide reasons for the delay. The notification includes: the nature of the breach including categories and approximate numbers of data subjects and records affected; the name and contact details of our Data Protection Officer or other contact point; the likely consequences of the breach; and measures taken or proposed to address the breach and mitigate its possible adverse effects. (3) Notification to affected individuals (Article 34 GDPR): when a personal data breach is likely to result in a high risk to the rights and freedoms of individuals, we communicate the breach to affected data subjects without undue delay, in clear and plain language. The communication describes the nature of the breach, provides contact details of our Data Protection Officer or relevant contact point, describes likely consequences and measures taken or proposed to address the breach and mitigate adverse effects. Notification to individuals is not required if: we have implemented appropriate technical and organizational protection measures (such as encryption) rendering data unintelligible to unauthorized persons; we have taken subsequent measures ensuring the high risk is no longer likely to materialize; or it would involve disproportionate effort, in which case we make a public communication or similar measure. (4) Internal documentation: we document all personal data breaches, including facts, effects and remedial action taken, to enable the supervisory authority to verify compliance with Article 33, even if notification is not required. (5) Investigation and remediation: conducting thorough investigations to determine root causes; implementing corrective and preventive measures to address vulnerabilities; reviewing and updating security measures and procedures; providing training and awareness to prevent future incidents. (6) Processor obligations: our data processing agreements require processors to notify us without undue delay after becoming aware of a personal data breach affecting our data, enabling us to meet our notification obligations.', 'fp-privacy' ); ?></p>

<?php if ( $dpo_name || $dpo_mail ) : ?>
<h2><?php echo esc_html__( 'Data Protection Officer', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( $dpo_name ); ?> — <?php echo esc_html( $dpo_mail ); ?></p>
<?php endif; ?>

<h2><?php echo esc_html__( 'Supervisory authority contact', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'Under Article 77 GDPR, you have the right to lodge a complaint with a supervisory authority if you believe that the processing of your personal data infringes data protection laws or that your privacy rights have been violated. You may lodge a complaint with a supervisory authority in: (i) the EU Member State of your habitual residence; (ii) your place of work; or (iii) the place where the alleged infringement took place. This right exists without prejudice to any other administrative or judicial remedy, meaning you can pursue a complaint with a supervisory authority in addition to or instead of seeking remedies through courts. The competent supervisory authority in each EU and EEA Member State is responsible for monitoring the application of the GDPR, handling complaints, conducting investigations and imposing administrative fines for violations. The supervisory authority to which the complaint has been lodged shall inform you of the progress and outcome of the complaint, including the possibility of a judicial remedy. Contact details, complaint forms and procedures for all EU and EEA supervisory authorities are available on the European Data Protection Board (EDPB) website at https://edpb.europa.eu/about-edpb/about-edpb/members_en. We are committed to working with supervisory authorities and resolving any complaints or concerns about our data processing practices. If you have concerns, we encourage you to contact us first so we can attempt to resolve the issue directly. However, you always have the right to lodge a complaint with a supervisory authority.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Policy governance and updates', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html__( 'We are committed to maintaining an accurate, transparent and up-to-date privacy policy that reflects our current data processing practices and complies with applicable legal requirements. We review and update this privacy policy at least annually, or more frequently when: (i) our processing operations, purposes, legal bases or data flows change significantly; (ii) new services, features or technologies are introduced; (iii) new legal requirements, regulations or guidance come into force; (iv) case law from the Court of Justice of the European Union or national courts affects our processing activities; (v) the European Data Protection Board or national supervisory authorities issue new guidelines, recommendations or binding decisions; or (vi) any other circumstances require updates to ensure continued alignment with GDPR, the ePrivacy Directive and framework, national implementations and sector-specific regulations in force as of October 2025. When we make material changes to this privacy policy that may affect your rights or how we process your personal data, we will communicate the updates through one or more of the following methods, as appropriate: (1) prominent notice on our website or within our services; (2) direct email notification to registered users; (3) in-app notifications or alerts; (4) requesting renewed consent where processing is based on consent and the changes affect the scope or purposes of processing; or (5) other appropriate means to ensure you are informed. For minor, non-material updates (such as formatting, clarifications, contact detail changes or updates to reflect organizational changes that do not affect processing), we may simply update the policy and modify the "last updated" date without separate notice. We encourage you to review this privacy policy periodically to stay informed about how we collect, use and protect your personal data. The current version of this policy is always available on our website. Previous versions may be available upon request.', 'fp-privacy' ); ?></p>

<h2><?php echo esc_html__( 'Services and cookies', 'fp-privacy' ); ?></h2>
<?php foreach ( $groups as $category => $services ) :
    $meta  = isset( $categories_meta[ $category ] ) && is_array( $categories_meta[ $category ] ) ? $categories_meta[ $category ] : array();
    $label = isset( $meta['label'] ) && '' !== $meta['label'] ? $meta['label'] : ucfirst( str_replace( '_', ' ', $category ) );
    $description = isset( $meta['description'] ) ? $meta['description'] : '';
    ?>
<div class="fp-privacy-category-block">
<h3><?php echo esc_html( $label ); ?></h3>
    <?php if ( $description ) : ?>
        <p class="fp-privacy-category-description"><?php echo wp_kses_post( $description ); ?></p>
    <?php endif; ?>
<table>
<thead>
<tr>
<th><?php echo esc_html__( 'Service', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Provider', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Purpose', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Cookies & Retention', 'fp-privacy' ); ?></th>
<th><?php echo esc_html__( 'Legal basis', 'fp-privacy' ); ?></th>
</tr>
</thead>
<tbody>
    <?php foreach ( $services as $service ) :
        if ( ! is_array( $service ) ) {
            continue;
        }

        $name        = fp_privacy_get_service_value( $service, 'name' );
        $provider    = fp_privacy_get_service_value( $service, 'provider' );
        $purpose     = fp_privacy_get_service_value( $service, 'purpose' );
        $retention   = fp_privacy_get_service_value( $service, 'retention' );
        $legal_basis = fp_privacy_get_service_value( $service, 'legal_basis' );
        $policy_url  = fp_privacy_get_service_value( $service, 'policy_url' );
        $service_cookies = fp_privacy_format_service_cookies( isset( $service['cookies'] ) ? $service['cookies'] : array() );
        ?>
<tr>
<td>
    <?php echo esc_html( $name ); ?>
    <?php if ( '' !== $policy_url ) : ?> — <a href="<?php echo esc_url( $policy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Privacy policy', 'fp-privacy' ); ?></a><?php endif; ?>
</td>
<td><?php echo esc_html( $provider ); ?></td>
<td><?php echo wp_kses_post( $purpose ); ?></td>
<td>
    <?php if ( ! empty( $service_cookies ) ) : ?>
        <span><?php echo esc_html( implode( '; ', $service_cookies ) ); ?></span>
    <?php else : ?>
        <span><?php echo esc_html__( 'No cookies declared.', 'fp-privacy' ); ?></span>
    <?php endif; ?>
    <?php if ( '' !== $retention ) : ?>
        <span> — <?php echo esc_html( $retention ); ?></span>
    <?php endif; ?>
</td>
<td><?php echo esc_html( $legal_basis ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>

<h2><?php echo esc_html__( 'Last update', 'fp-privacy' ); ?></h2>
<p><?php echo esc_html( sprintf( __( 'This policy was generated on %s.', 'fp-privacy' ), $last_generated ) ); ?></p>
</section>
