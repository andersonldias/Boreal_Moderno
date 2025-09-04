import Link from 'next/link';

export default function HomePage() {
  return (
    <div className="d-flex align-items-center justify-content-center vh-100">
      <div className="text-center">
        <h1>Bem-vindo ao Boreal Moderno</h1>
        <p className="lead">A solução moderna para gestão de esquadrias.</p>
        <Link href="/dashboard" passHref>
          <button className="btn btn-primary">Acessar Dashboard</button>
        </Link>
      </div>
    </div>
  );
}
