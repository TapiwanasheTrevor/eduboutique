import { MessageCircle } from 'lucide-react';
import { generateWhatsAppLink } from '../../utils/helpers';

const WhatsAppButton = () => {
  const whatsappLink = generateWhatsAppLink('Hello! I have a question about your books.');

  return (
    <a
      href={whatsappLink}
      target="_blank"
      rel="noopener noreferrer"
      className="fixed bottom-6 right-6 bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600 transition-all hover:scale-110 z-50"
      aria-label="Chat on WhatsApp"
    >
      <MessageCircle className="w-6 h-6" />
    </a>
  );
};

export default WhatsAppButton;
