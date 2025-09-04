import 'bootstrap/dist/css/bootstrap.min.css';

export const metadata = {
  title: 'Boreal Moderno - Gestão de Esquadrias',
  description: 'Aplicação moderna para gestão de obras e instalações.',
};

export default function RootLayout({ children }) {
  return (
    <html lang="pt-BR">
      <body>{children}</body>
    </html>
  );
}
