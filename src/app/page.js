'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';

export default function HomePage() {
  const router = useRouter();

  useEffect(() => {
    // Redireciona automaticamente para a página de login
    router.push('/login');
  }, [router]);

  // Caso o redirecionamento automático não funcione, mostramos um link
  return (
    <div className="d-flex align-items-center justify-content-center vh-100">
      <div className="text-center">
        <h1>Bem-vindo ao Boreal Moderno</h1>
        <p className="lead">A solução moderna para gestão de esquadrias.</p>
        <p>Redirecionando para a página de login...</p>
        <Link href="/login" passHref>
          <button className="btn btn-primary">Ir para Login</button>
        </Link>
      </div>
    </div>
  );
}
