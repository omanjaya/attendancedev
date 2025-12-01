import { useState, useEffect } from 'react';

/**
 * Check if current device is mobile based on screen width and user agent
 */
export const isMobileDevice = (): boolean => {
  if (typeof window === 'undefined') return false;

  // Check screen size (mobile < 768px)
  const isMobileSize = window.innerWidth < 768;

  // Check user agent for mobile devices
  const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
  const isMobileUA = mobileRegex.test(navigator.userAgent);

  return isMobileSize || isMobileUA;
};

/**
 * Get device type: mobile, tablet, or desktop
 */
export const getDeviceType = (): 'mobile' | 'tablet' | 'desktop' => {
  if (typeof window === 'undefined') return 'desktop';

  const width = window.innerWidth;

  if (width < 768) return 'mobile';
  if (width < 1024) return 'tablet';
  return 'desktop';
};

/**
 * React hook to detect if device is mobile with reactive updates
 */
export const useIsMobile = (): boolean => {
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    // Initial check
    const checkMobile = () => {
      setIsMobile(isMobileDevice());
    };

    checkMobile();

    // Add resize listener for responsive changes
    window.addEventListener('resize', checkMobile);

    return () => {
      window.removeEventListener('resize', checkMobile);
    };
  }, []);

  return isMobile;
};

/**
 * React hook to get device type with reactive updates
 */
export const useDeviceType = (): 'mobile' | 'tablet' | 'desktop' => {
  const [deviceType, setDeviceType] = useState<'mobile' | 'tablet' | 'desktop'>('desktop');

  useEffect(() => {
    const checkDevice = () => {
      setDeviceType(getDeviceType());
    };

    checkDevice();
    window.addEventListener('resize', checkDevice);

    return () => {
      window.removeEventListener('resize', checkDevice);
    };
  }, []);

  return deviceType;
};
