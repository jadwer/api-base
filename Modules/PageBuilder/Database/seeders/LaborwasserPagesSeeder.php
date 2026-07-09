<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PageBuilder\Models\Page;
use Modules\User\Models\User;
use Carbon\Carbon;

/**
 * Labor Wasser de Mexico — tenant-specific static pages seeder.
 *
 * This seeder creates the 6 static pages used by LWM's public website:
 * - nosotros (About Us)
 * - laboratorios (Labs)
 * - certificados (Certificates of Analysis)
 * - aviso-privacidad (Privacy Notice)
 * - derechos-reservados (Terms of Use)
 * - catalogos (Catalogs)
 *
 * Idempotency: every page uses firstOrCreate on slug, so re-running this
 * seeder never overwrites edits made through the PageBuilder dashboard.
 * To force a reset of a single page, run:
 *   Page::where('slug', 'nosotros')->delete();
 *   php artisan db:seed --class=LaborwasserPagesSeeder
 *
 * Expected to run AFTER CleanDatabaseSeeder in fresh deploys, since it needs
 * the system user (User::find(1)) to be already created.
 */
class LaborwasserPagesSeeder extends Seeder
{
    /**
     * Seed static pages for the public site.
     * These replace hardcoded React pages with Page Builder managed content.
     */
    public function run(): void
    {
        $system = User::find(1) ?? User::first();

        $pages = [
            $this->nosotros(),
            $this->laboratorios(),
            $this->certificados(),
            $this->avisoPrivacidad(),
            $this->derechosReservados(),
            $this->catalogos(),
        ];

        foreach ($pages as $pageData) {
            Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                array_merge($pageData, [
                    'user_id' => $system->id,
                    'status' => 'published',
                    'published_at' => Carbon::now(),
                ])
            );
        }
    }

    private function sharedCss(): string
    {
        return <<<'CSS'
.hero-sections {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  padding: 60px 0;
  color: white;
}
.hero-sections h1 {
  font-size: 2.5rem;
  font-weight: bold;
  text-transform: uppercase;
  margin: 0;
}
.cta-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 60px 0;
  color: white;
}
.cta-section h2 {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 15px;
}
.cta-section p {
  font-size: 1.1rem;
  opacity: 0.9;
  margin-bottom: 25px;
}
.about-section p {
  font-size: 1.1rem;
  line-height: 1.8;
  color: #333;
}
.mission-card {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 30px;
  height: 100%;
}
.mission-card h2 {
  color: #1e3c72;
  margin-bottom: 15px;
}
.values-section {
  background: #f0f4f8;
  padding: 60px 0;
}
.values-section h4 {
  color: #1e3c72;
  margin-top: 15px;
}
.why-buy-section {
  background: #e8f5e9;
  padding: 60px 0;
}
.brand-card {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  transition: transform 0.2s, box-shadow 0.2s;
}
.brand-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.brand-card img {
  max-height: 80px;
  object-fit: contain;
  margin-bottom: 15px;
}
.brand-card a {
  display: inline-block;
  background: #8AC905;
  color: white;
  padding: 8px 20px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
}
.brand-card a:hover {
  background: #7ab504;
}
.legal-content h2 {
  color: #1e3c72;
  font-size: 1.5rem;
  margin-top: 30px;
  margin-bottom: 15px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e9ecef;
}
.legal-content h3 {
  color: #2a5298;
  font-size: 1.2rem;
  margin-top: 20px;
  margin-bottom: 10px;
}
.legal-content p {
  font-size: 1rem;
  line-height: 1.8;
  color: #333;
  margin-bottom: 15px;
}
.legal-content ul {
  margin-bottom: 15px;
}
.legal-content li {
  line-height: 1.8;
  color: #333;
  margin-bottom: 5px;
}
.legal-content a {
  color: #8AC905;
}
.legal-footer {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid #dee2e6;
  color: #6c757d;
  font-size: 0.9rem;
}
CSS;
    }

    private function ctaHtml(): string
    {
        return <<<'HTML'
<section class="cta-section">
  <div class="container text-center">
    <h2>¿NECESITAS UNA COTIZACIÓN?</h2>
    <p>Ponte en contacto con nosotros y uno de nuestros representantes se pondrá en contacto contigo.</p>
    <a href="/productos" class="btn btn-success btn-lg" style="padding: 12px 30px; font-size: 1.1rem;">Cotiza ahora</a>
  </div>
</section>
HTML;
    }

    private function nosotros(): array
    {
        $cta = $this->ctaHtml();

        return [
            'title' => 'Acerca de Nosotros',
            'slug' => 'nosotros',
            'css' => $this->sharedCss(),
            'html' => <<<HTML
<div class="hero-sections">
  <div class="container">
    <div class="row align-items-center">
      <div class="col">
        <h1>ACERCA DE NOSOTROS</h1>
      </div>
    </div>
  </div>
</div>

<div class="container my-5 about-section">
  <div class="row align-items-center">
    <div class="col-12 col-md-6 mb-4 mb-md-0">
      <img src="/images/laborwasser/labor-wasser-mexico-about.webp" class="img-fluid rounded" alt="Labor Wasser de México" style="max-width: 100%; border-radius: 12px;" />
    </div>
    <div class="col-12 col-md-6">
      <p>Bienvenidos a Labor Wasser de México, una empresa líder en la comercialización de productos y equipos para laboratorio. Con más de 20 años de experiencia en el sector, nos hemos consolidado como un proveedor confiable y comprometido con la calidad y la innovación.</p>
      <p>Nos mantenemos a la vanguardia de las innovaciones tecnológicas y científicas para ofrecer siempre las soluciones más avanzadas a nuestros clientes. Participamos en ferias y congresos internacionales, y colaboramos con instituciones académicas y de investigación para estar al tanto de las últimas tendencias y avances en el campo.</p>
      <p>En Labor Wasser de México, su éxito es nuestra prioridad. Confíe en nosotros para proporcionarle los productos y el soporte que necesita para llevar a cabo su trabajo con excelencia y precisión.</p>
    </div>
  </div>
</div>

<div class="container mb-5">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="mission-card">
        <h2>Misión</h2>
        <p>LWM es un factor clave para el desarrollo de la industria y el laboratorio, ya que ofrecemos soluciones e ingenierías innovadoras para todos nuestros usuarios tanto en la parte operativa como en la parte científica. Ponemos nuestra experiencia en tus manos y no solo es un producto, damos una solución a la problemática del día a día.</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mission-card">
        <h2>Visión</h2>
        <p>Consolidarnos como una de las mejores empresas en el suministro de materiales, equipos y servicios de la industria nacional, asegurando la satisfacción de cada uno de nuestros clientes y socios comerciales, logrando esto solo con nuestra respuesta inmediata y la amplia experiencia.</p>
      </div>
    </div>
  </div>
</div>

<div class="values-section">
  <div class="container">
    <div class="row text-center">
      <div class="col-12 mb-4">
        <h2 style="color: #1e3c72; font-size: 2rem; font-weight: bold;">Valores LWM</h2>
      </div>
      <div class="col-6 col-md mb-4">
        <div style="font-size: 3rem; color: #8AC905;"><i class="bi bi-heart-fill"></i></div>
        <h4>Pasión</h4>
        <p>Por nuestras actividades diarias, clientes y proveedores</p>
      </div>
      <div class="col-6 col-md mb-4">
        <div style="font-size: 3rem; color: #8AC905;"><i class="bi bi-trophy-fill"></i></div>
        <h4>Competitividad</h4>
        <p>Gratitud y lealtad en general para nuestro entorno</p>
      </div>
      <div class="col-6 col-md mb-4">
        <div style="font-size: 3rem; color: #8AC905;"><i class="bi bi-shield-check"></i></div>
        <h4>Responsabilidad</h4>
        <p>Gratitud y lealtad en general para nuestro entorno</p>
      </div>
      <div class="col-6 col-md mb-4">
        <div style="font-size: 3rem; color: #8AC905;"><i class="bi bi-hand-thumbs-up-fill"></i></div>
        <h4>Honestidad</h4>
        <p>Hacia nuestros clientes, proveedores y equipo de trabajo</p>
      </div>
      <div class="col-6 col-md mb-4">
        <div style="font-size: 3rem; color: #8AC905;"><i class="bi bi-star-fill"></i></div>
        <h4>Calidad</h4>
        <p>En cada producto y servicio que ofrecemos</p>
      </div>
    </div>
  </div>
</div>

<div class="why-buy-section">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-12 col-md-8 mx-auto">
        <h3 style="color: #1e3c72; font-weight: bold; margin-bottom: 20px;">¿Por qué comprar con nosotros?</h3>
        <p>Por la calidad en nuestro servicio, por la experiencia técnica y la resolución de la problemática tanto en la parte analítica como en la parte de proceso, además de que somos una empresa innovadora con tecnología de vanguardia, trabajamos a través de un CRM y un ERP para un mejor servicio, así como la concentración de las mejores marcas para la parte analítica y de proceso para la industria y la investigación, especialistas en químicos y tratamientos en aguas.</p>
        <a href="/productos" class="btn btn-success mt-3" style="padding: 10px 25px;">Ver Productos</a>
      </div>
    </div>
  </div>
</div>

{$cta}
HTML,
        ];
    }

    private function laboratorios(): array
    {
        $cta = $this->ctaHtml();

        return [
            'title' => 'Laboratorios',
            'slug' => 'laboratorios',
            'css' => $this->sharedCss(),
            'html' => <<<HTML
<div class="hero-sections">
  <div class="container">
    <div class="row align-items-center">
      <div class="col">
        <h1>LABORATORIOS</h1>
      </div>
    </div>
  </div>
</div>

<div class="container my-5">
  <div class="row">
    <div class="col-12">
      <p style="font-size: 1.2rem; color: #666;">Próximamente: Mosaico de laboratorios con los que trabajamos.</p>
    </div>
  </div>
</div>

{$cta}
HTML,
        ];
    }

    private function certificados(): array
    {
        $cta = $this->ctaHtml();

        return [
            'title' => 'Certificados de Análisis',
            'slug' => 'certificados',
            'css' => $this->sharedCss(),
            'html' => <<<HTML
<div class="hero-sections">
  <div class="container">
    <div class="row align-items-center">
      <div class="col">
        <h1>CERTIFICADOS DE ANÁLISIS</h1>
      </div>
    </div>
  </div>
</div>

<div class="container my-5">
  <h2 style="color: #1e3c72; margin-bottom: 30px;">Marcas</h2>
  <div class="row row-cols-2 row-cols-md-4 g-4">
    <div class="col"><div class="brand-card">
      <img src="/images/logos/avantor-labor-wasser.webp" alt="Avantor" />
      <a href="https://cheminfo.avantorsciences.com/store/search/searchPartnersCertSds.jsp" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/band-labor-wasser.webp" alt="Brand" />
      <a href="https://www.brand.de/es/servicio-de-ayuda/mi-producto" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/bd-labor-wasser.webp" alt="BD" />
      <a href="https://regdocs.bd.com/regdocs/qcinfo" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/biomerieux-labor-wasser.webp" alt="bioMérieux" />
      <a href="https://www.3eonline.com/EeeOnlinePortal/DesktopDefault.aspx" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/condalab-labor-wasser.webp" alt="Condalab" />
      <a href="https://www.condalab.com/int/es/documentos" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/dwk-labor-wasser.webp" alt="DWK" />
      <a href="https://cert.dwk.com/login" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/eisco-labor-wasser.webp" alt="EISCO" />
      <a href="https://www.eiscoindustrial.com/pages/glassware-certificates" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/fisher.webp" alt="Fisher Scientific" />
      <a href="https://www.fishersci.com/us/en/catalog/search/certificates.html" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/hach-labor-wasser.webp" alt="Hach" />
      <a href="https://app.hach.com/coaweb/customer_coa_request.asp" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/hanna-labor-wasser.webp" alt="Hanna Instruments" />
      <a href="https://certificates.hannainst.com/vi" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/honeywell-labor-wasser.webp" alt="Honeywell" />
      <a href="https://lab.honeywell.com/en/sds" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/merck-labor-wasser.webp" alt="Merck" />
      <a href="https://www.merckmillipore.com" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/microbiologics-labor-wasser.webp" alt="Microbiologics" />
      <a href="https://www.microbiologics.com/certificate-of-analysis" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/microflex-labor-wasser.webp" alt="Microflex" />
      <a href="https://www.microbiologics.com/certificate-of-analysis" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/micron-labor-wasser.webp" alt="Myron L" />
      <a href="https://www.myronl.com/downloads/" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/productos-quimicos-monterrey-labor-wasser.webp" alt="PQM" />
      <a href="http://148.244.138.146:88/eCertPqm/" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/thermo-labor-wasser.webp" alt="Thermo Fisher" />
      <a href="https://www.thermofisher.com/document-connect/document-connect.html" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/toronto-labor-wasser.webp" alt="Toronto Research" />
      <a href="https://www.lgcstandards.com/GB/en/search?q=trc&searchIn=documents%3Acoa" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/usp-labor-wasser.webp" alt="USP" />
      <a href="https://store.usp.org/home" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/vwr-labor-wasser.webp" alt="VWR" />
      <a href="https://us.vwr.com/store/search/searchCerts.jsp?tabId=certSearch" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/whirl-labor-wasser.webp" alt="Whirl-Pak" />
      <a href="https://www.whirl-pak.com/sterility-document/" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
  </div>
</div>

{$cta}
HTML,
        ];
    }

    private function avisoPrivacidad(): array
    {
        $cta = $this->ctaHtml();
        $year = date('Y');

        return [
            'title' => 'Aviso de Privacidad',
            'slug' => 'aviso-privacidad',
            'css' => $this->sharedCss(),
            'html' => <<<HTML
<div class="hero-sections">
  <div class="container">
    <div class="row align-items-center">
      <div class="col">
        <h1>AVISO DE PRIVACIDAD</h1>
      </div>
    </div>
  </div>
</div>

<div class="container my-5 legal-content">
  <p style="color: #6c757d; font-size: 0.9rem;">Última actualización: 21 de enero de 2026</p>

  <h2>1. Identidad del Responsable</h2>
  <p><strong>Labor Wasser de México S.A. de C.V.</strong> (en adelante "Labor Wasser"), con domicilio en Av. Industrial 123, Col. Centro Industrial, C.P. 64000, Monterrey, Nuevo León, México, es responsable del tratamiento de sus datos personales.</p>
  <p>Para cualquier duda o aclaración respecto al presente aviso de privacidad, puede contactarnos a través de:</p>
  <ul>
    <li>Correo electrónico: <a href="mailto:privacidad@laborwasser.mx">privacidad@laborwasser.mx</a></li>
    <li>Teléfono: +52 (81) 1234-5678</li>
  </ul>

  <h2>2. Datos Personales Recabados</h2>
  <p>Para las finalidades señaladas en este aviso de privacidad, podemos recabar las siguientes categorías de datos personales:</p>
  <h3>Datos de identificación:</h3>
  <ul>
    <li>Nombre completo</li>
    <li>Razón social (en caso de personas morales)</li>
    <li>RFC (Registro Federal de Contribuyentes)</li>
    <li>CURP</li>
    <li>Domicilio fiscal y de entrega</li>
  </ul>
  <h3>Datos de contacto:</h3>
  <ul>
    <li>Correo electrónico</li>
    <li>Número telefónico fijo y/o móvil</li>
    <li>Dirección postal</li>
  </ul>
  <h3>Datos comerciales:</h3>
  <ul>
    <li>Historial de compras y cotizaciones</li>
    <li>Preferencias de productos</li>
    <li>Información de facturación</li>
  </ul>
  <h3>Datos financieros:</h3>
  <ul>
    <li>Información bancaria para pagos y transferencias</li>
    <li>Datos de tarjetas de crédito/débito (procesados por terceros certificados PCI-DSS)</li>
  </ul>

  <h2>3. Finalidades del Tratamiento</h2>
  <h3>Finalidades primarias (necesarias):</h3>
  <ul>
    <li>Procesar solicitudes de cotización y pedidos</li>
    <li>Facturación y cobranza</li>
    <li>Entrega de productos</li>
    <li>Atención al cliente y soporte técnico</li>
    <li>Cumplimiento de obligaciones legales y fiscales</li>
    <li>Gestión de devoluciones y garantías</li>
  </ul>
  <h3>Finalidades secundarias (opcionales):</h3>
  <ul>
    <li>Envío de boletines informativos y promociones</li>
    <li>Encuestas de satisfacción</li>
    <li>Invitaciones a eventos y capacitaciones</li>
    <li>Análisis estadísticos para mejora del servicio</li>
  </ul>
  <p>Si no desea que sus datos sean tratados para finalidades secundarias, puede manifestarlo enviando un correo a <a href="mailto:privacidad@laborwasser.mx">privacidad@laborwasser.mx</a>.</p>

  <h2>4. Transferencia de Datos</h2>
  <p>Sus datos personales podrán ser transferidos a:</p>
  <ul>
    <li><strong>Empresas de mensajería y paquetería:</strong> Para la entrega de productos</li>
    <li><strong>Instituciones bancarias:</strong> Para procesamiento de pagos</li>
    <li><strong>Autoridades fiscales:</strong> Para cumplimiento de obligaciones legales (SAT)</li>
    <li><strong>Proveedores de servicios tecnológicos:</strong> Para almacenamiento seguro de información</li>
  </ul>
  <p>Las transferencias anteriores no requieren su consentimiento conforme al artículo 37 de la LFPDPPP.</p>

  <h2>5. Derechos ARCO</h2>
  <p>Usted tiene derecho a conocer qué datos personales tenemos de usted, para qué los utilizamos y las condiciones del uso que les damos (Acceso). Asimismo, es su derecho solicitar la corrección de su información personal en caso de que esté desactualizada, sea inexacta o incompleta (Rectificación); que la eliminemos de nuestros registros o bases de datos cuando considere que la misma no está siendo utilizada adecuadamente (Cancelación); así como oponerse al uso de sus datos personales para fines específicos (Oposición).</p>
  <p>Para ejercer sus derechos ARCO, envíe su solicitud a <a href="mailto:privacidad@laborwasser.mx">privacidad@laborwasser.mx</a> con:</p>
  <ul>
    <li>Nombre completo y datos de contacto</li>
    <li>Descripción clara de los derechos que desea ejercer</li>
    <li>Documentos que acrediten su identidad (copia de INE o pasaporte)</li>
  </ul>
  <p>Responderemos en un plazo máximo de 20 días hábiles.</p>

  <h2>6. Uso de Cookies y Tecnologías de Rastreo</h2>
  <p>Nuestro sitio web utiliza cookies y otras tecnologías de rastreo para mejorar su experiencia de navegación, analizar el tráfico del sitio y personalizar el contenido mostrado.</p>
  <p>Puede deshabilitar las cookies en la configuración de su navegador, aunque esto podría afectar algunas funcionalidades del sitio.</p>

  <h2>7. Medidas de Seguridad</h2>
  <p>Labor Wasser implementa medidas de seguridad administrativas, técnicas y físicas para proteger sus datos personales contra daño, pérdida, alteración, destrucción o uso, acceso o tratamiento no autorizado, incluyendo:</p>
  <ul>
    <li>Encriptación SSL/TLS en todas las comunicaciones</li>
    <li>Almacenamiento seguro con acceso restringido</li>
    <li>Capacitación del personal en protección de datos</li>
    <li>Auditorías periódicas de seguridad</li>
  </ul>

  <h2>8. Cambios al Aviso de Privacidad</h2>
  <p>Labor Wasser se reserva el derecho de modificar este aviso de privacidad en cualquier momento. Las modificaciones serán publicadas en nuestro sitio web y, cuando sean significativas, le notificaremos por correo electrónico.</p>

  <h2>9. Autoridad de Protección de Datos</h2>
  <p>Si considera que sus derechos han sido vulnerados, puede presentar una queja ante el Instituto Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales (INAI).</p>
  <p>Sitio web: <a href="https://www.inai.org.mx" target="_blank" rel="noopener noreferrer">www.inai.org.mx</a></p>

  <div class="legal-footer">
    <p>&copy; {$year} Labor Wasser de México S.A. de C.V. Todos los derechos reservados.</p>
  </div>
</div>

{$cta}
HTML,
        ];
    }

    private function derechosReservados(): array
    {
        $cta = $this->ctaHtml();
        $year = date('Y');

        return [
            'title' => 'Derechos Reservados y Términos de Uso',
            'slug' => 'derechos-reservados',
            'css' => $this->sharedCss(),
            'html' => <<<HTML
<div class="hero-sections">
  <div class="container">
    <div class="row align-items-center">
      <div class="col">
        <h1>DERECHOS RESERVADOS Y TÉRMINOS DE USO</h1>
      </div>
    </div>
  </div>
</div>

<div class="container my-5 legal-content">
  <p style="color: #6c757d; font-size: 0.9rem;">Última actualización: 21 de enero de 2026</p>

  <h2>1. Propiedad Intelectual</h2>
  <p>Todo el contenido de este sitio web, incluyendo pero no limitado a textos, gráficos, logotipos, iconos, imágenes, clips de audio, descargas digitales, compilaciones de datos y software, es propiedad de <strong>Labor Wasser de México S.A. de C.V.</strong> o de sus proveedores de contenido y está protegido por las leyes mexicanas e internacionales de propiedad intelectual.</p>
  <p>Las marcas comerciales, logotipos y marcas de servicio (colectivamente las "Marcas") mostradas en este sitio son marcas registradas y no registradas de Labor Wasser de México y de terceros. Nada en este sitio debe interpretarse como una concesión de licencia o derecho para usar cualquier Marca.</p>

  <h2>2. Uso Permitido del Sitio</h2>
  <p>Se le otorga una licencia limitada para acceder y hacer uso personal de este sitio. Usted NO puede:</p>
  <ul>
    <li>Descargar o modificar cualquier parte del sitio sin autorización expresa por escrito</li>
    <li>Usar el sitio o su contenido con fines comerciales sin autorización</li>
    <li>Reproducir, duplicar, copiar, vender, revender o explotar comercialmente cualquier parte del sitio</li>
    <li>Utilizar técnicas de minería de datos, robots o herramientas similares de recopilación</li>
    <li>Enmarcar o utilizar técnicas de enmarcado para incluir marcas comerciales o contenido propietario</li>
    <li>Usar meta tags u otro "texto oculto" utilizando el nombre o marcas de Labor Wasser</li>
  </ul>
  <p>Cualquier uso no autorizado terminará los permisos o licencias otorgados por Labor Wasser de México.</p>

  <h2>3. Términos de Venta</h2>
  <h3>3.1 Cotizaciones</h3>
  <ul>
    <li>Las cotizaciones tienen una vigencia indicada en el documento (generalmente 15-30 días)</li>
    <li>Los precios están sujetos a cambios sin previo aviso antes de la aceptación formal</li>
    <li>Las cotizaciones no constituyen un contrato vinculante hasta ser aceptadas por escrito</li>
  </ul>
  <h3>3.2 Precios e Impuestos</h3>
  <ul>
    <li>Los precios mostrados son en Moneda Nacional (MXN) salvo que se indique lo contrario</li>
    <li>Todos los precios están sujetos al 16% de IVA</li>
    <li>Precios en USD están sujetos al tipo de cambio del día de facturación</li>
  </ul>
  <h3>3.3 Formas de Pago</h3>
  <ul>
    <li>Transferencia electrónica (SPEI)</li>
    <li>Depósito bancario</li>
    <li>Tarjeta de crédito/débito (procesado por Stripe)</li>
    <li>Crédito comercial (sujeto a aprobación y línea de crédito)</li>
  </ul>
  <h3>3.4 Tiempos de Entrega</h3>
  <ul>
    <li>Los tiempos de entrega (ETA) son estimados y pueden variar según disponibilidad</li>
    <li>Productos de importación pueden tener tiempos extendidos (4-8 semanas)</li>
    <li>Pedidos urgentes pueden estar sujetos a cargos adicionales</li>
  </ul>

  <h2>4. Política de Devoluciones</h2>
  <p>Labor Wasser de México acepta devoluciones bajo las siguientes condiciones:</p>
  <ul>
    <li>Productos defectuosos o dañados durante el transporte: Reporte dentro de 48 horas</li>
    <li>Productos en perfecto estado: Dentro de 15 días, en empaque original sin abrir</li>
    <li>Reactivos y químicos: No se aceptan devoluciones por razones de seguridad (excepto defectos)</li>
    <li>Equipos especiales o sobre pedido: No se aceptan devoluciones</li>
  </ul>
  <p>Los gastos de envío de la devolución corren por cuenta del cliente, excepto en casos de error de Labor Wasser o productos defectuosos.</p>

  <h2>5. Garantías</h2>
  <p>Los productos distribuidos por Labor Wasser de México cuentan con la garantía del fabricante según las condiciones establecidas por cada marca. Labor Wasser actúa como intermediario en la gestión de garantías.</p>
  <ul>
    <li>Equipos: Garantía según fabricante (típicamente 1-2 años)</li>
    <li>Consumibles: Sin garantía después de apertura del empaque</li>
    <li>Reactivos: Garantía de fecha de caducidad indicada en etiqueta</li>
  </ul>
  <p>La garantía no cubre daños por mal uso, modificaciones no autorizadas, o uso fuera de especificaciones del fabricante.</p>

  <h2>6. Limitación de Responsabilidad</h2>
  <p>Labor Wasser de México no será responsable por daños indirectos, incidentales, especiales, consecuentes o punitivos, incluyendo pero no limitado a pérdida de ganancias, datos, uso, buena voluntad u otras pérdidas intangibles, resultantes de:</p>
  <ul>
    <li>Su uso o incapacidad para usar el sitio o los productos</li>
    <li>Cualquier conducta o contenido de terceros en el sitio</li>
    <li>Contenido obtenido del sitio</li>
    <li>Acceso no autorizado, uso o alteración de sus transmisiones o contenido</li>
  </ul>
  <p>En ningún caso la responsabilidad total de Labor Wasser excederá el monto pagado por usted por los productos o servicios en cuestión.</p>

  <h2>7. Contenido de Terceros</h2>
  <p>Este sitio puede contener enlaces a sitios web de terceros. Estos enlaces se proporcionan únicamente para su conveniencia. Labor Wasser no tiene control sobre el contenido de estos sitios y no asume responsabilidad por el contenido, políticas de privacidad o prácticas de sitios de terceros.</p>

  <h2>8. Disponibilidad del Sitio</h2>
  <p>Labor Wasser no garantiza que el sitio esté disponible de manera ininterrumpida, segura o libre de errores. Nos reservamos el derecho de modificar, suspender o discontinuar cualquier parte del sitio en cualquier momento sin previo aviso.</p>

  <h2>9. Ley Aplicable y Jurisdicción</h2>
  <p>Estos términos se regirán e interpretarán de acuerdo con las leyes de los Estados Unidos Mexicanos. Cualquier disputa relacionada con estos términos se someterá a la jurisdicción exclusiva de los tribunales competentes de Monterrey, Nuevo León, México.</p>

  <h2>10. Cambios a los Términos</h2>
  <p>Labor Wasser se reserva el derecho de modificar estos términos en cualquier momento. Las modificaciones entrarán en vigor inmediatamente después de su publicación en el sitio. El uso continuado del sitio después de cualquier cambio constituye su aceptación de los nuevos términos.</p>

  <h2>11. Contacto</h2>
  <p>Para preguntas sobre estos términos, contáctenos:</p>
  <ul>
    <li>Correo: <a href="mailto:legal@laborwasser.mx">legal@laborwasser.mx</a></li>
    <li>Teléfono: +52 (81) 1234-5678</li>
    <li>Dirección: Av. Industrial 123, Col. Centro Industrial, C.P. 64000, Monterrey, N.L.</li>
  </ul>

  <h2>Documentos Relacionados</h2>
  <ul>
    <li><a href="/aviso-privacidad">Aviso de Privacidad</a></li>
    <li><a href="/certificados">Certificados de Análisis</a></li>
  </ul>

  <div class="legal-footer">
    <p>&copy; {$year} Labor Wasser de México S.A. de C.V. Todos los derechos reservados.</p>
  </div>
</div>

{$cta}
HTML,
        ];
    }

    private function catalogos(): array
    {
        $cta = $this->ctaHtml();

        return [
            'title' => 'Catálogos',
            'slug' => 'catalogos',
            'css' => $this->sharedCss(),
            'html' => <<<HTML
<div class="hero-sections">
  <div class="container">
    <div class="row align-items-center">
      <div class="col">
        <h1>CATÁLOGOS</h1>
      </div>
    </div>
  </div>
</div>

<div class="container my-5">
  <h2 style="color: #1e3c72; margin-bottom: 30px;">Marcas</h2>
  <div class="row row-cols-2 row-cols-md-4 g-4">
    <div class="col"><div class="brand-card">
      <img src="/images/logos/apera-labor-wasser.webp" alt="Apera" />
      <a href="/catalogs/apr-cat-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/cobetter-labor-wasser.webp" alt="Cobetter" />
      <a href="/catalogs/cobetter-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/eisco-labor-wasser.webp" alt="EISCO" />
      <a href="/catalogs/eisco-cat-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/productos-quimicos-monterrey-labor-wasser.webp" alt="Fermon (PQM)" />
      <a href="/catalogs/fermon-cat-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/hanna-labor-wasser.webp" alt="Hanna" />
      <a href="/catalogs/hanna-cat-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/high-purity-labor-wasser.webp" alt="High Purity" />
      <a href="/catalogs/high-purity-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/imparlab-labor-wasser.webp" alt="Imparlab" />
      <a href="/catalogs/imparlab-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
    <div class="col"><div class="brand-card">
      <img src="/images/logos/micron-labor-wasser.webp" alt="Myron L" />
      <a href="/catalogs/myron-labor-wasser.pdf" target="_blank" rel="noopener noreferrer">CONSULTAR</a>
    </div></div>
  </div>
</div>

{$cta}
HTML,
        ];
    }
}
