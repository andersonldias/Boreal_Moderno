'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

const navLinks = [
  { href: '/dashboard', text: 'Dashboard', icon: 'fa-tachometer-alt' },
  { href: '/obras', text: 'Gerenciar Obras', icon: 'fa-building' },
  { href: '/funcionarios', text: 'Funcionários', icon: 'fa-users' },
  { href: '/usuarios', text: 'Usuários', icon: 'fa-user-cog' }, // Gerenciado pelo Supabase, mas pode ser uma view
  { href: '/instalacoes', text: 'Instalações', icon: 'fa-tools' },
  { href: '/relatorios', text: 'Relatórios', icon: 'fa-chart-bar' },
  { href: '/fotos', text: 'Fotos', icon: 'fa-camera' },
];

export default function Sidebar() {
  const pathname = usePathname();

  const sidebarStyle = {
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
  };

  const linkStyle = {
    color: 'rgba(255,255,255,0.8)',
    borderRadius: '10px',
    margin: '2px 0',
    transition: 'all 0.3s',
  };

  const activeLinkStyle = {
    ...linkStyle,
    color: 'white',
    background: 'rgba(255,255,255,0.1)',
  };

  return (
    <nav className="col-md-3 col-lg-2 d-md-block collapse" style={sidebarStyle}>
      <div className="position-sticky pt-3">
        <div className="text-center mb-4">
          <i className="fas fa-building text-white" style={{ fontSize: '2rem' }}></i>
          <h5 className="text-white mt-2">Instalação de Esquadrias</h5>
          <small className="text-white-50">Controle de Obras</small>
        </div>
        
        <ul className="nav flex-column">
          {navLinks.map(link => (
            <li className="nav-item" key={link.href}>
              <Link href={link.href} className="nav-link px-3"
                  style={pathname === link.href ? activeLinkStyle : linkStyle}>
                <i className={`fas ${link.icon} me-2`}></i>
                {link.text}
              </Link>
            </li>
          ))}
          
          <li className="nav-item mt-4">
            <button 
              className="nav-link btn btn-link text-start p-3 w-100 border-0"
              style={linkStyle}
              onClick={() => {
                // Implementar logout
                window.location.href = '/login';
              }}
            >
              <i className="fas fa-sign-out-alt me-2"></i>
              Sair
            </button>
          </li>
        </ul>
      </div>
    </nav>
  );
}
